<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\KrsSubmissionService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $lecturerUser = User::factory()->create(['level' => User::ROLE_DOSEN]);
        $program = ProgramStudi::query()->create(['kode' => 'TI', 'nama' => 'Teknologi Informasi', 'jenjang' => 'S1', 'aktif' => true]);
        $lecturer = Dosen::query()->create(['user_id' => $lecturerUser->id, 'nama' => 'Dosen Pengampu Uji', 'program_studi_id' => $program->id, 'aktif' => true]);
        $semester = Semester::query()->create(['kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1, 'aktif' => true, 'krs_mulai_pada' => now('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'), 'krs_selesai_pada' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s')]);
        $course = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'TI101', 'nama' => 'Basis Data Uji', 'sks' => 3]);
        $studentUser = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $student = Mahasiswa::query()->create(['user_id' => $studentUser->id, 'nim' => 'TI-0001', 'nama' => 'Mahasiswa Uji Terisolasi', 'angkatan' => 2026, 'program_studi_id' => $program->id]);

        return compact('admin', 'lecturerUser', 'program', 'lecturer', 'semester', 'course', 'studentUser', 'student');
    }

    private function payload(array $data, string $section = 'A'): array
    {
        return ['mata_kuliah_id' => $data['course']->id, 'semester_id' => $data['semester']->id, 'dosen_id' => $data['lecturer']->id, 'kode_kelas' => $section, 'kapasitas' => 30, 'ruang' => 'Lab'];
    }

    private function enrollment(array $data, string $status = 'disetujui'): KrsDetail
    {
        $class = KelasKuliah::query()->create($this->payload($data));
        $krs = Krs::query()->create(['mahasiswa_id' => $data['student']->id, 'semester_id' => $data['semester']->id, 'status' => $status]);

        return KrsDetail::query()->create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
    }

    public function test_admin_class_crud_search_detail_and_krs_integration(): void
    {
        Http::preventStrayRequests();
        $d = $this->data();
        $base = 'dashboard.admin-akademik.kelas-kuliah.';
        $this->actingAs($d['admin'])->get(route($base.'index'))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 0 && $records->isEmpty())
            ->assertSee('Data operasional belum tersedia.')
            ->assertDontSee('Tidak ada data sesuai pilihan.');
        $this->actingAs($d['admin'])->post(route($base.'store'), $this->payload($d))->assertRedirect()->assertSessionHasNoErrors();
        $class = KelasKuliah::query()->firstOrFail();
        $this->get(route($base.'index', ['q' => 'TI101']))->assertOk()->assertSee('Basis Data Uji', false);
        $this->get(route($base.'index', ['q' => 'tidak-ada']))->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 0 && $records->isEmpty())
            ->assertSee('<strong>0</strong> kelas kuliah ditemukan', false)
            ->assertSee('Tidak ada data sesuai pilihan.')
            ->assertDontSee('Data operasional belum tersedia.')
            ->assertDontSee('Basis Data Uji');
        $this->get(route($base.'show', $class))->assertOk()->assertSee('Dosen Pengampu Uji');
        $this->get(route($base.'edit', $class))->assertOk();
        $this->put(route($base.'update', $class), $this->payload($d) + [])->assertSessionHasNoErrors();
        $this->post(route($base.'store'), $this->payload($d))->assertSessionHasErrors('kode_kelas');
        $detail = app(KrsSubmissionService::class)->addClass($d['studentUser'], $class->id);
        $this->assertSame($class->id, $detail->kelas_kuliah_id);
        $this->delete(route($base.'destroy', $class))->assertSessionHasErrors('kelas');
        $this->put(route($base.'update', $class), array_replace($this->payload($d), ['kode_kelas' => 'B']))->assertSessionHasErrors('kode_kelas');
        $this->assertDatabaseHas('kelas_kuliah', ['id' => $class->id, 'kode_kelas' => 'A']);
        $free = KelasKuliah::query()->create($this->payload($d, 'FREE'));
        $this->delete(route($base.'destroy', $free))->assertRedirect();
        $this->assertDatabaseMissing('kelas_kuliah', ['id' => $free->id]);
        Http::assertNothingSent();
    }

    public function test_class_validation_and_every_admin_route_reject_wrong_roles(): void
    {
        $d = $this->data();
        $class = KelasKuliah::query()->create($this->payload($d));
        $this->actingAs($d['admin'])->post(route('dashboard.admin-akademik.kelas-kuliah.store'), array_replace($this->payload($d, 'B'), ['kapasitas' => 0, 'ruang' => str_repeat('x', 51)]))->assertSessionHasErrors(['kapasitas', 'ruang']);
        $d['course']->update(['kode' => 'DEMO-MK']);
        $this->post(route('dashboard.admin-akademik.kelas-kuliah.store'), $this->payload($d, 'B'))->assertSessionHasErrors('mata_kuliah_id');
        foreach ([1, 2, 4] as $level) {
            $actor = User::factory()->create(['level' => $level]);
            $this->actingAs($actor);
            foreach (['index', 'create', 'show', 'edit'] as $action) {
                $this->get(route('dashboard.admin-akademik.kelas-kuliah.'.$action, in_array($action, ['show', 'edit']) ? $class : []))->assertForbidden();
            }
            $this->post(route('dashboard.admin-akademik.kelas-kuliah.store'), $this->payload($d))->assertForbidden();
            $this->put(route('dashboard.admin-akademik.kelas-kuliah.update', $class), $this->payload($d))->assertForbidden();
            $this->delete(route('dashboard.admin-akademik.kelas-kuliah.destroy', $class))->assertForbidden();
        }
    }

    public function test_grade_entry_edit_finalization_and_final_lock_use_configured_points(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d);
        $save = route('dashboard.dosen.nilai.save', $detail->id);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Mahasiswa Uji Terisolasi')->assertSee('Simpan nilai');
        $this->put($save, ['nilai_angka' => 82.5, 'nilai_huruf' => 'AB', 'nilai_mutu' => 99, 'final_at' => now()])->assertSessionHasNoErrors();
        $grade = Nilai::query()->firstOrFail();
        $this->assertSame('3.50', $grade->nilai_mutu);
        $this->assertNull($grade->final_at);
        $revision = GradeEntryService::revision($grade);
        $this->put($save, ['nilai_angka' => 90, 'nilai_huruf' => 'A', 'revision' => $revision])->assertSessionHasNoErrors();
        $this->put($save, ['nilai_angka' => 70, 'nilai_huruf' => 'B', 'revision' => $revision])->assertSessionHasErrors('nilai');
        $this->assertDatabaseCount('nilai', 1);
        $this->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasNoErrors();
        $this->put($save, ['nilai_angka' => 20, 'nilai_huruf' => 'E'])->assertSessionHasErrors('nilai');
        $this->assertSame('90.00', $grade->fresh()->nilai_angka);
        $this->assertSame(4.0, app(AcademicGradeSummary::class)->forStudent($d['student'])['ipk']);
    }

    public function test_grade_validation_idor_and_unapproved_enrollment(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d, 'menunggu');
        $save = route('dashboard.dosen.nilai.save', $detail->id);
        $this->actingAs($d['lecturerUser'])->put($save, ['nilai_angka' => 101, 'nilai_huruf' => 'A+'])->assertSessionHasErrors(['nilai_angka', 'nilai_huruf']);
        $this->put($save, ['nilai_angka' => 80, 'nilai_huruf' => 'B'])->assertNotFound();
        $detail->krs->update(['status' => 'disetujui']);
        $other = User::factory()->create(['level' => 2]);
        Dosen::query()->create(['user_id' => $other->id, 'nama' => 'Dosen Lain', 'aktif' => true]);
        $this->actingAs($other)->put($save, ['nilai_angka' => 80, 'nilai_huruf' => 'B'])->assertNotFound();
        $this->get(route('dashboard.dosen.nilai'))->assertDontSee('Mahasiswa Uji Terisolasi');
        foreach ([1, 3, 4] as $role) {
            $this->actingAs(User::factory()->create(['level' => $role]))->put($save, ['nilai_angka' => 80, 'nilai_huruf' => 'B'])->assertForbidden();
        }
        $this->assertDatabaseCount('nilai', 0);
    }

    public function test_finalization_checks_schema_before_any_grade_model_query(): void
    {
        $user = User::factory()->create(['level' => 2]);
        Schema::drop('nilai');
        $queries = [];
        DB::connection()->beforeExecuting(function ($query) use (&$queries): void {
            if (preg_match('/\bfrom\s+["`]?nilai\b/i', $query)) {
                $queries[] = $query;
            }
        });
        $this->actingAs($user)->post(route('dashboard.dosen.nilai.finalize', 999))->assertStatus(503);
        $this->put(route('dashboard.dosen.nilai.save', 999), [])->assertStatus(503);
        $this->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Pengelolaan nilai belum tersedia');
        $this->assertSame([], $queries);
        $this->actingAs(User::factory()->create(['level' => 1]))->post(route('dashboard.dosen.nilai.finalize', 999))->assertForbidden();
    }

    public function test_failed_grade_persistence_does_not_leave_partial_row(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d);
        $shouldFail = true;
        DB::connection()->beforeExecuting(function ($query) use (&$shouldFail): void {
            if ($shouldFail && str_contains(strtolower($query), 'insert into "nilai"')) {
                $shouldFail = false;
                throw new \RuntimeException('Isolated persistence failure');
            }
        });
        try {
            app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 80, 'nilai_huruf' => 'B']);
            $this->fail('Expected transaction failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Isolated persistence failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('nilai', 0);
    }

    public function test_owner_and_lecturer_dashboards_aggregate_database_values_and_filter(): void
    {
        Http::preventStrayRequests();
        $d = $this->data();
        $detail = $this->enrollment($d);
        $grade = Nilai::query()->create(['krs_detail_id' => $detail->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        $analysis = AnalisisRisiko::query()->create(['mahasiswa_id' => $d['student']->id, 'semester_id' => $d['semester']->id, 'skor' => .2, 'kategori' => 'rendah', 'dianalisis_pada' => now()]);
        $recommendation = RekomendasiIntervensi::query()->create(['analisis_risiko_id' => $analysis->id, 'judul' => 'Pendampingan Uji', 'prioritas' => 'normal', 'status' => 'direkomendasikan']);
        TindakLanjut::query()->create(['rekomendasi_intervensi_id' => $recommendation->id, 'status' => 'direncanakan']);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen'))->assertOk()->assertViewHas('stats', fn ($s) => $s['students'] === 1 && $s['final'] === 1 && $s['pending'] === 0)->assertSee('Basis Data Uji');
        $owner = User::factory()->create(['level' => 4]);
        $response = $this->actingAs($owner)->get(route('dashboard.owner'))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['students'] === 1 && $s['ipk'] === 4.0)
            ->assertViewHas('recommendations', fn ($items) => $items->all() === ['direkomendasikan' => 1])
            ->assertViewHas('riskDistribution', fn ($items) => $items->all() === ['rendah' => 1]);
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $this->assertTrue($document->loadHTML($response->getContent()));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//section[h3[normalize-space(.)="Rekomendasi tercatat"]]/dl/div[dt/span[normalize-space(.)="Direkomendasikan"] and dd[normalize-space(.)="1"]]')->length);
        $this->assertSame(1, $xpath->query('//section[h3[normalize-space(.)="Risiko dari analisis terbaru per mahasiswa"]]/dl/div[dt/span[normalize-space(.)="Rendah"] and dd[normalize-space(.)="1"]]')->length);
        $other = ProgramStudi::query()->create(['kode' => 'CS', 'nama' => 'Cyber Security', 'jenjang' => 'S1']);
        $this->get(route('dashboard.owner', ['program_studi_id' => $other->id]))->assertOk()->assertSee('Data operasional belum tersedia');
        $this->get(route('dashboard.owner', ['semester_id' => 999]))->assertSessionHasErrors('semester_id');
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.owner'))->assertForbidden();
        $this->assertDatabaseCount('analisis_risiko', 1);
        Http::assertNothingSent();
    }

    public function test_known_dummy_provenance_and_demo_accounts_cannot_leak_into_operational_views(): void
    {
        $d = $this->data();
        AnalisisRisiko::query()->create(['mahasiswa_id' => $d['student']->id, 'skor' => .99, 'kategori' => 'risiko-sintetis', 'ringkasan' => ['sumber' => 'data_dummy'], 'dianalisis_pada' => now()]);
        $this->actingAs($d['studentUser'])->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertDontSee('risiko-sintetis')->assertSee('Analisis risiko belum tersedia');
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner'))->assertOk()->assertDontSee('risiko-sintetis');
        $d['studentUser']->update(['email' => 'fixture@demo.uninlar.test']);
        $this->actingAs($d['studentUser'])->get(route('dashboard.mahasiswa.profil'))->assertOk()->assertSee('Profil Mahasiswa Belum Tersedia')->assertDontSee('TI-0001');
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner'))->assertSee('Data operasional belum tersedia');
    }

    public function test_demo_lecturer_profile_is_not_eligible_even_on_non_demo_class(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d);
        $d['lecturer']->update(['nidn' => 'DEMO-LECTURER']);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen'))->assertOk()->assertSee('Profil dosen belum tertaut');
        $this->put(route('dashboard.dosen.nilai.save', $detail->id), ['nilai_angka' => 90, 'nilai_huruf' => 'A'])->assertNotFound();
        $this->assertDatabaseCount('nilai', 0);
    }

    public function test_class_pagination_preserves_search_and_excludes_demo_relations(): void
    {
        $d = $this->data();
        foreach (range(1, 16) as $i) {
            KelasKuliah::query()->create($this->payload($d, 'SECTION-'.$i));
        }
        $demoCourse = MataKuliah::query()->create(['program_studi_id' => $d['program']->id, 'kode' => 'DEMO-MK', 'nama' => 'Mata kuliah sintetis', 'sks' => 3]);
        KelasKuliah::query()->create(array_replace($this->payload($d, 'SECTION-DEMO'), ['mata_kuliah_id' => $demoCourse->id]));
        $url = route('dashboard.admin-akademik.kelas-kuliah.index', ['q' => 'SECTION']);
        $this->actingAs($d['admin'])->get($url)->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 16 && $p->count() === 15)->assertSee('q=SECTION', false)->assertDontSee('SECTION-DEMO');
        $this->get($url.'&page=2')->assertOk()->assertViewHas('records', fn ($p) => $p->count() === 1);
    }

    public function test_capacity_cannot_be_lowered_below_reserved_places(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d);
        $other = Mahasiswa::query()->create(['nim' => 'TI-0002', 'nama' => 'Peserta Uji Kedua', 'angkatan' => 2026, 'program_studi_id' => $d['program']->id]);
        $pending = Krs::query()->create(['mahasiswa_id' => $other->id, 'semester_id' => $d['semester']->id, 'status' => 'menunggu']);
        KrsDetail::query()->create(['krs_id' => $pending->id, 'kelas_kuliah_id' => $detail->kelas_kuliah_id]);
        $this->actingAs($d['admin'])->put(route('dashboard.admin-akademik.kelas-kuliah.update', $detail->kelas_kuliah_id), array_replace($this->payload($d), ['kapasitas' => 1]))->assertSessionHasErrors('kapasitas');
        $this->assertDatabaseHas('kelas_kuliah', ['id' => $detail->kelas_kuliah_id, 'kapasitas' => 30]);
    }

    public function test_missing_lecturer_schema_is_controlled_and_route_identifiers_are_numeric(): void
    {
        $actor = User::factory()->create(['level' => 2]);
        Schema::drop('dosen');
        $this->actingAs($actor)->get(route('dashboard.dosen'))->assertOk()->assertSee('Schema akademik belum tersedia');
        $this->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Pengelolaan nilai belum tersedia');
        $this->post('/dashboard/dosen/nilai/not-a-number/finalisasi')->assertNotFound();
        $this->post(route('dashboard.dosen.nilai.finalize', 1))->assertStatus(503);
    }

    public function test_final_grades_cannot_be_deleted_and_invalid_numeric_values_are_rejected(): void
    {
        $d = $this->data();
        $detail = $this->enrollment($d);
        $grade = Nilai::query()->create(['krs_detail_id' => $detail->id, 'nilai_angka' => 101, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);
        $this->actingAs($d['lecturerUser'])->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasErrors('nilai');
        $grade->update(['nilai_angka' => 90]);
        $this->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasNoErrors();
        try {
            $grade->fresh()->delete();
            $this->fail('Final grade should be retained.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('nilai', ['id' => $grade->id]);
        }
    }

    public function test_partial_upstream_schema_cannot_be_reported_as_grade_ready(): void
    {
        $user = User::factory()->create(['level' => 2]);
        Schema::table('dosen', fn ($table) => $table->dropColumn('aktif'));
        $this->assertFalse(app(GradeFinalizationService::class)->workflowAvailable());
        $this->actingAs($user)->post(route('dashboard.dosen.nilai.finalize', 999))->assertStatus(503);
        $this->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Pengelolaan nilai belum tersedia');
    }

    public function test_owner_risk_distribution_uses_latest_stored_analysis_and_semester_filter(): void
    {
        $d = $this->data();
        foreach (['rendah', 'tinggi'] as $index => $category) {
            AnalisisRisiko::query()->create(['mahasiswa_id' => $d['student']->id, 'semester_id' => $d['semester']->id, 'skor' => .2 + $index / 2, 'kategori' => $category, 'dianalisis_pada' => now()->addMinutes($index)]);
        }
        $owner = User::factory()->create(['level' => 4]);
        $this->actingAs($owner)->get(route('dashboard.owner'))->assertOk()->assertViewHas('riskDistribution', fn ($items) => $items->all() === ['tinggi' => 1]);
        $other = Semester::query()->create(['kode' => '2025-GENAP', 'tahun_ajaran' => '2025/2026', 'periode' => 2, 'aktif' => false]);
        $this->get(route('dashboard.owner', ['semester_id' => $other->id]))->assertOk()->assertViewHas('riskDistribution', fn ($items) => $items->isEmpty())->assertViewHas('stats', fn ($stats) => $stats['krs'] === 0);
        $this->assertDatabaseCount('analisis_risiko', 2);
    }
}
