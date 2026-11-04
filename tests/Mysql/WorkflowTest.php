<?php

namespace Tests\Mysql;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\KrsPengajuanRiwayat;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

class WorkflowTest extends MysqlTestCase
{
    use DatabaseTransactions;

    public function test_rejection_resubmission_snapshot_and_approval(): void
    {
        $d = MysqlFixtures::make();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $first = $service->submit($d['users'][0]);
        $snapshot = $first->snapshot_kelas;
        app(KrsDecisionService::class)->decide($first->id, $d['admin'], 'ditolak', 'Periksa pilihan');
        $second = $service->submit($d['users'][0]);
        $this->assertSame(2, $second->versi);
        $this->assertEquals($snapshot, $first->fresh()->snapshot_kelas);
        $this->assertSame('ditolak', $first->fresh()->status);
        app(KrsDecisionService::class)->decide($second->id, $d['admin'], 'disetujui', null);
        $this->assertSame('disetujui', $second->fresh()->krs->status);
        $this->assertSame($d['admin']->id, $second->fresh()->diputuskan_oleh_user_id);
    }

    public function test_failed_submission_is_atomic_on_mysql(): void
    {
        $d = MysqlFixtures::make();
        $detail = app(KrsSubmissionService::class)->addClass($d['users'][0], $d['class']->id);
        $fail = true;
        DB::listen(function ($query) use (&$fail): void {
            if ($fail && str_starts_with(strtolower($query->sql), 'insert into `krs_pengajuan_riwayat`')) {
                $fail = false;
                throw new \RuntimeException('QA forced persistence failure');
            }
        });
        try {
            app(KrsSubmissionService::class)->submit($d['users'][0]);
            $this->fail('Expected persistence failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('QA forced persistence failure', $e->getMessage());
        }
        $this->assertSame('draft', $detail->fresh()->krs->status);
        $this->assertNull($detail->fresh()->krs->diajukan_pada);
        $this->assertSame(0, KrsPengajuanRiwayat::where('krs_id', $detail->krs_id)->count());
    }

    public function test_grade_entry_finalization_and_gpa(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $entry = app(GradeEntryService::class);
        $grade = $entry->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 80, 'nilai_huruf' => 'AB']);
        $this->assertSame('3.50', $grade->nilai_mutu);
        $this->assertNull(app(AcademicGradeSummary::class)->forStudent($d['profiles'][0])['ipk']);
        app(GradeFinalizationService::class)->finalize($d['lecturerUser'], $grade->id);
        $summary = app(AcademicGradeSummary::class)->forStudent($d['profiles'][0]);
        $this->assertSame(3.5, $summary['ips']);
        $this->assertSame(3.5, $summary['ipk']);
        try {
            $entry->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 90, 'nilai_huruf' => 'A']);
            $this->fail('Final grade must be immutable');
        } catch (ValidationException) {
            $this->assertSame('80.00', $grade->fresh()->nilai_angka);
        }
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen'))->assertOk();
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner'))->assertOk();
    }

    public function test_class_constraints_authorization_and_demo_filter(): void
    {
        $d = MysqlFixtures::make();
        $payload = $d['class']->only(['mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas', 'kapasitas']);
        $base = 'dashboard.admin-akademik.kelas-kuliah.';
        $this->actingAs($d['admin'])->post(route($base.'store'), $payload)->assertSessionHasErrors('kode_kelas');
        $this->post(route($base.'store'), array_replace($payload, ['kode_kelas' => 'B']))->assertSessionHasNoErrors();
        $this->get(route($base.'index', ['q' => $d['course']->kode]))->assertOk()->assertSee($d['course']->nama);
        MysqlFixtures::detail($d);
        $this->delete(route($base.'destroy', $d['class']->id))->assertSessionHasErrors('kelas');
        foreach ([1, 2, 4] as $level) {
            $this->actingAs(User::factory()->create(['level' => $level]))->post(route($base.'store'), $payload)->assertForbidden();
        }
        $d['profiles'][0]->update(['nim' => 'DEMO-QA-'.$d['profiles'][0]->id]);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.profil'))->assertOk()->assertSee('Profil Mahasiswa Belum Tersedia')->assertDontSee($d['profiles'][0]->nim);
    }

    public function test_database_enforces_unique_history_and_restrict_foreign_key(): void
    {
        $d = MysqlFixtures::make();
        app(KrsSubmissionService::class)->addClass($d['users'][0], $d['class']->id);
        $history = app(KrsSubmissionService::class)->submit($d['users'][0]);
        foreach (['duplicate', 'delete'] as $action) {
            try {
                DB::transaction(function () use ($history, $action): void {
                    if ($action === 'delete') {
                        DB::table('users')->where('id', $history->diajukan_oleh_user_id)->delete();
                    } else {
                        $attributes = $history->getAttributes();
                        unset($attributes['id']);
                        DB::table('krs_pengajuan_riwayat')->insert($attributes);
                    }
                });
                $this->fail('MySQL should reject '.$action);
            } catch (QueryException $e) {
                $this->assertSame('23000', $e->errorInfo[0]);
            }
        }
        $this->assertDatabaseHas('krs_pengajuan_riwayat', ['id' => $history->id]);
    }

    public function test_gpa_ignores_invalid_unfinalized_grades_and_uses_latest_course_attempt(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 75, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => now()]);
        $prior = Semester::create(['kode' => 'QA-PRIOR-'.bin2hex(random_bytes(3)), 'tahun_ajaran' => '2025/2026', 'periode' => 2, 'aktif' => false]);
        $priorClass = KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $prior->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'PRIOR']);
        $priorKrs = Krs::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $prior->id, 'status' => 'disetujui']);
        $priorDetail = KrsDetail::create(['krs_id' => $priorKrs->id, 'kelas_kuliah_id' => $priorClass->id]);
        Nilai::create(['krs_detail_id' => $priorDetail->id, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => '2025-12-01 00:00:00']);
        foreach (['valid', 'invalid', 'unfinalized'] as $kind) {
            $course = MataKuliah::create(['program_studi_id' => $d['program']->id, 'kode' => 'QA-'.bin2hex(random_bytes(4)), 'nama' => 'QA '.$kind, 'sks' => 1]);
            $class = KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'A']);
            $extra = KrsDetail::create(['krs_id' => $detail->krs_id, 'kelas_kuliah_id' => $class->id]);
            Nilai::create(['krs_detail_id' => $extra->id, 'nilai_angka' => $kind === 'invalid' ? 101 : 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => $kind === 'unfinalized' ? null : now()]);
        }
        $summary = app(AcademicGradeSummary::class)->forStudent($d['profiles'][0]);
        $this->assertSame(3.25, $summary['ips']);
        $this->assertSame(3.25, $summary['ipk']);
        $this->assertSame(3, $summary['final_count']);
    }

    public function test_grade_routes_enforce_assigned_lecturer_on_mysql(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $other = User::factory()->create(['level' => 2]);
        Dosen::create(['user_id' => $other->id, 'nama' => 'QA Unassigned', 'aktif' => true]);
        $url = route('dashboard.dosen.nilai.save', $detail->id);
        $values = ['nilai_angka' => 80, 'nilai_huruf' => 'B'];
        $this->actingAs($other)->put($url, $values)->assertNotFound();
        $this->actingAs($d['users'][0])->put($url, $values)->assertForbidden();
        $this->actingAs($d['lecturerUser'])->put($url, $values)->assertSessionHasNoErrors();
        $grade = Nilai::where('krs_detail_id', $detail->id)->firstOrFail();
        $this->actingAs($other)->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertNotFound();
        $this->assertNull($grade->fresh()->final_at);
        $this->actingAs($d['lecturerUser'])->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasNoErrors();
        $this->assertNotNull($grade->fresh()->final_at);
    }
}
