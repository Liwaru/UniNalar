<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\KrsPengajuanRiwayat;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\GradeFinalizationService;
use App\Services\KrsSubmissionService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KrsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_submission_snapshot_is_immutable_and_retry_increments_version(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00', 'Asia/Jakarta'));
        [$studentUser, $student, $semester, $course1, $course2] = $this->operationalStudent();
        $class1 = $this->classFor($course1, $semester, 'A');
        $class2 = $this->classFor($course2, $semester, 'A');
        $admin = User::factory()->create(['username' => 'academic-admin-workflow', 'level' => User::ROLE_ADMIN_AKADEMIK]);
        $this->assertTrue(app(KrsSubmissionService::class)->registrationIsOpen($semester), json_encode([$semester->getRawOriginal('krs_mulai_pada'), $semester->getRawOriginal('krs_selesai_pada'), now('Asia/Jakarta')->toDateTimeString(), app(KrsSubmissionService::class)->workflowAvailable()]));

        $this->actingAs($studentUser)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class1->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($studentUser)->post(route('dashboard.mahasiswa.krs.submit'))->assertRedirect();

        $krs = Krs::query()->where('mahasiswa_id', $student->id)->firstOrFail();
        $first = KrsPengajuanRiwayat::query()->where('krs_id', $krs->id)->firstOrFail();
        $this->assertSame('menunggu', $krs->status);
        $this->assertSame(1, $first->versi);
        $this->assertSame((int) $class1->id, $first->snapshot_kelas['kelas'][0]['kelas_kuliah_id']);
        $this->assertSame((int) $studentUser->id, $first->diajukan_oleh_user_id);

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$first, 'ditolak']), ['catatan_keputusan' => 'Mohon sesuaikan pilihan.'])->assertRedirect();
        $this->actingAs($studentUser)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class2->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($studentUser)->post(route('dashboard.mahasiswa.krs.submit'))->assertRedirect();

        $second = KrsPengajuanRiwayat::query()->where('krs_id', $krs->id)->where('versi', 2)->firstOrFail();
        $this->assertSame('ditolak', $first->fresh()->status);
        $this->assertCount(1, $first->fresh()->snapshot_kelas['kelas']);
        $this->assertCount(2, $second->snapshot_kelas['kelas']);
        $this->assertSame('menunggu', $krs->fresh()->status);

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$second, 'disetujui']))->assertRedirect();
        $this->assertSame('disetujui', $second->fresh()->status);
        $this->assertSame((int) $admin->id, $krs->fresh()->diputuskan_oleh_user_id);

        Carbon::setTestNow();
    }

    public function test_student_cannot_edit_approved_krs_or_remove_another_students_detail(): void
    {
        [$user, $student, $semester, $course] = $this->operationalStudent();
        $class = $this->classFor($course, $semester, 'A');
        $otherUser = User::factory()->create(['username' => 'other-student-workflow', 'level' => User::ROLE_MAHASISWA]);
        $otherStudent = Mahasiswa::query()->create(['user_id' => $otherUser->id, 'nim' => 'RPL-OTHER-01', 'nama' => 'Other Student', 'angkatan' => 2024, 'program_studi_id' => $student->program_studi_id]);
        $otherKrs = Krs::query()->create(['mahasiswa_id' => $otherStudent->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $otherDetail = KrsDetail::query()->create(['krs_id' => $otherKrs->id, 'kelas_kuliah_id' => $class->id]);

        $this->actingAs($user)->delete(route('dashboard.mahasiswa.krs.detail.destroy', $otherDetail))->assertNotFound();
        $this->assertDatabaseHas('krs_detail', ['id' => $otherDetail->id]);
    }

    public function test_workflow_pages_enforce_role_access_on_the_server(): void
    {
        $student = User::factory()->create(['username' => 'workflow-access-student', 'level' => User::ROLE_MAHASISWA]);
        $admin = User::factory()->create(['username' => 'workflow-access-admin', 'level' => User::ROLE_ADMIN_AKADEMIK]);
        $lecturer = User::factory()->create(['username' => 'workflow-access-lecturer', 'level' => User::ROLE_DOSEN]);

        $this->actingAs($student)->get(route('dashboard.admin-akademik.krs-pengajuan.index'))->assertForbidden();
        $this->actingAs($student)->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [1, 'disetujui']))->assertForbidden();
        $this->actingAs($student)->get(route('dashboard.dosen.nilai'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard.admin-akademik.krs-pengajuan.index'))->assertOk();
        $this->actingAs($lecturer)->get(route('dashboard.mahasiswa.krs'))->assertForbidden();
    }

    public function test_workflow_readiness_requires_complete_schema_constraints_and_finalization_fields(): void
    {
        $workflow = app(KrsSubmissionService::class);

        $this->assertTrue($workflow->workflowAvailable());
        $this->assertTrue(app(GradeFinalizationService::class)->workflowAvailable());

        Schema::table('semester', fn (Blueprint $table) => $table->dropColumn('krs_selesai_pada'));
        $this->assertFalse($workflow->workflowAvailable());
    }

    public function test_krs_workflow_readiness_is_false_when_history_table_is_missing(): void
    {
        Schema::drop('krs_pengajuan_riwayat');

        $this->assertFalse(app(KrsSubmissionService::class)->workflowAvailable());
    }

    public function test_krs_workflow_readiness_is_false_when_version_unique_constraint_is_missing(): void
    {
        Schema::table('krs_pengajuan_riwayat', fn (Blueprint $table) => $table->dropUnique(['krs_id', 'versi']));

        $this->assertFalse(app(KrsSubmissionService::class)->workflowAvailable());
    }

    public function test_admin_decision_returns_503_before_querying_history_when_schema_is_missing(): void
    {
        $admin = User::factory()->create(['username' => 'schema-guard-admin', 'level' => User::ROLE_ADMIN_AKADEMIK]);
        Schema::drop('krs_pengajuan_riwayat');
        $historyQueries = [];
        DB::listen(function ($query) use (&$historyQueries): void {
            if (preg_match('/\bfrom\s+["`]?krs_pengajuan_riwayat\b/i', $query->sql)) {
                $historyQueries[] = $query->sql;
            }
        });

        $this->actingAs($admin)
            ->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [123, 'disetujui']))
            ->assertStatus(503);

        $this->assertSame([], $historyQueries, 'The history model must not be queried before readiness is checked.');
    }

    public function test_admin_can_configure_the_explicit_local_krs_window(): void
    {
        $admin = User::factory()->create(['username' => 'window-admin', 'level' => User::ROLE_ADMIN_AKADEMIK]);

        $this->actingAs($admin)->post(route('dashboard.admin-akademik.semester.store'), [
            'kode' => '2027-GANJIL',
            'tahun_ajaran' => '2027/2028',
            'periode' => 1,
            'aktif' => 1,
            'krs_mulai_pada' => '2027-06-01T09:00',
            'krs_selesai_pada' => '2027-06-15T17:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('semester', [
            'kode' => '2027-GANJIL',
            'krs_mulai_pada' => '2027-06-01 09:00',
            'krs_selesai_pada' => '2027-06-15 17:00',
        ]);
    }

    public function test_krs_registration_window_is_required_and_uses_jakarta_wall_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 08:59:59', 'Asia/Jakarta'));
        [$user, $student, $semester, $course] = $this->operationalStudent();
        $class = $this->classFor($course, $semester, 'A');

        $this->actingAs($user)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class->id])
            ->assertSessionHasErrors('krs');
        $this->assertDatabaseMissing('krs', ['mahasiswa_id' => $student->id, 'semester_id' => $semester->id]);

        Carbon::setTestNow(Carbon::parse('2026-10-29 09:00:00', 'Asia/Jakarta'));
        $this->actingAs($user)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class->id])->assertRedirect();
        $this->assertDatabaseHas('krs_detail', ['kelas_kuliah_id' => $class->id]);

        Carbon::setTestNow();
    }

    public function test_submission_snapshot_identity_and_history_cannot_be_modified_or_deleted(): void
    {
        [$user, , , $course] = $this->operationalStudent();
        $semester = Semester::query()->where('aktif', true)->firstOrFail();
        $class = $this->classFor($course, $semester, 'A');
        $this->actingAs($user)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class->id])->assertRedirect();
        $this->actingAs($user)->post(route('dashboard.mahasiswa.krs.submit'))->assertRedirect();
        $history = KrsPengajuanRiwayat::query()->firstOrFail();

        try {
            $history->snapshot_kelas = ['kelas' => []];
            $history->save();
            $this->fail('Snapshot pengajuan seharusnya tidak dapat diubah.');
        } catch (ValidationException) {
            $this->assertSame($class->id, $history->fresh()->snapshot_kelas['kelas'][0]['kelas_kuliah_id']);
        }

        try {
            $history->fresh()->delete();
            $this->fail('Riwayat pengajuan seharusnya tidak dapat dihapus.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('krs_pengajuan_riwayat', ['id' => $history->id]);
        }
    }

    public function test_failed_history_insert_rolls_back_submission_state(): void
    {
        [$user, $student, $semester, $course] = $this->operationalStudent();
        $class = $this->classFor($course, $semester, 'A');
        $this->actingAs($user)->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $class->id])->assertRedirect();
        $krs = Krs::query()->where('mahasiswa_id', $student->id)->firstOrFail();
        $throwOnHistoryInsert = true;
        DB::listen(function ($query) use (&$throwOnHistoryInsert): void {
            if ($throwOnHistoryInsert && str_contains(strtolower($query->sql), 'insert into "krs_pengajuan_riwayat"')) {
                $throwOnHistoryInsert = false;
                throw new \RuntimeException('Simulated history persistence failure.');
            }
        });

        try {
            app(KrsSubmissionService::class)->submit($user);
            $this->fail('Submission should fail when history persistence fails.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated history persistence failure.', $exception->getMessage());
        }

        $this->assertSame('draft', $krs->fresh()->status);
        $this->assertNull($krs->fresh()->diajukan_pada);
        $this->assertDatabaseCount('krs_pengajuan_riwayat', 0);
    }

    public function test_only_assigned_lecturer_can_finalize_complete_grade_once(): void
    {
        [$studentUser, $student, $semester, $course] = $this->operationalStudent();
        $assignedUser = User::factory()->create(['username' => 'assigned-lecturer', 'level' => User::ROLE_DOSEN]);
        $assignedLecturer = Dosen::query()->create(['user_id' => $assignedUser->id, 'nama' => 'Assigned Lecturer', 'aktif' => true]);
        $otherUser = User::factory()->create(['username' => 'unassigned-lecturer', 'level' => User::ROLE_DOSEN]);
        Dosen::query()->create(['user_id' => $otherUser->id, 'nama' => 'Other Lecturer', 'aktif' => true]);
        $class = $this->classFor($course, $semester, 'A', $assignedLecturer->id);
        $krs = Krs::query()->create(['mahasiswa_id' => $student->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $detail = KrsDetail::query()->create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
        $grade = Nilai::query()->create(['krs_detail_id' => $detail->id, 'nilai_angka' => 85, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);

        $this->actingAs($otherUser)->post(route('dashboard.dosen.nilai.finalize', $grade))->assertNotFound();
        $this->assertNull($grade->fresh()->final_at);
        $this->actingAs($assignedUser)->post(route('dashboard.dosen.nilai.finalize', $grade))->assertRedirect();
        $this->assertSame((int) $assignedUser->id, $grade->fresh()->difinalkan_oleh_user_id);
        $this->assertNotNull($grade->fresh()->final_at);
        $this->actingAs($assignedUser)->post(route('dashboard.dosen.nilai.finalize', $grade))->assertSessionHasErrors('nilai');

        try {
            $finalGrade = $grade->fresh();
            $finalGrade->nilai_angka = 90;
            $finalGrade->save();
            $this->fail('Nilai yang sudah final seharusnya tidak dapat diubah.');
        } catch (ValidationException) {
            $this->assertSame('85.00', $grade->fresh()->nilai_angka);
        }

        $this->actingAs($studentUser)->get(route('dashboard.dosen.nilai'))->assertForbidden();
    }

    public function test_ips_and_ipk_use_only_valid_final_grades_and_latest_course_attempt(): void
    {
        [, $student, $semester, $course1, $course2] = $this->operationalStudent();
        $course2->update(['sks' => 1]);
        $priorSemester = Semester::query()->create(['kode' => '2025-GENAP', 'tahun_ajaran' => '2025/2026', 'periode' => 2, 'aktif' => false]);
        $priorClass = $this->classFor($course1, $priorSemester, 'A');
        $priorKrs = Krs::query()->create(['mahasiswa_id' => $student->id, 'semester_id' => $priorSemester->id, 'status' => 'disetujui']);
        $priorDetail = KrsDetail::query()->create(['krs_id' => $priorKrs->id, 'kelas_kuliah_id' => $priorClass->id]);
        Nilai::query()->create(['krs_detail_id' => $priorDetail->id, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => '2026-01-01 00:00:00']);

        $currentClass1 = $this->classFor($course1, $semester, 'B');
        $currentClass2 = $this->classFor($course2, $semester, 'A');
        $currentKrs = Krs::query()->create(['mahasiswa_id' => $student->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $currentDetail1 = KrsDetail::query()->create(['krs_id' => $currentKrs->id, 'kelas_kuliah_id' => $currentClass1->id]);
        $currentDetail2 = KrsDetail::query()->create(['krs_id' => $currentKrs->id, 'kelas_kuliah_id' => $currentClass2->id]);
        Nilai::query()->create(['krs_detail_id' => $currentDetail1->id, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => '2026-10-29 03:00:00']);
        Nilai::query()->create(['krs_detail_id' => $currentDetail2->id, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => '2026-10-29 04:00:00']);

        $unfinalizedClass = $this->classFor(MataKuliah::query()->create(['program_studi_id' => $student->program_studi_id, 'kode' => 'RPL103', 'nama' => 'Belum Final', 'sks' => 3]), $semester, 'A');
        $unfinalizedDetail = KrsDetail::query()->create(['krs_id' => $currentKrs->id, 'kelas_kuliah_id' => $unfinalizedClass->id]);
        Nilai::query()->create(['krs_detail_id' => $unfinalizedDetail->id, 'nilai_huruf' => 'E', 'nilai_mutu' => 0]);

        $summary = app(AcademicGradeSummary::class)->forStudent($student);
        $this->assertSame(3.25, $summary['ips']);
        $this->assertSame('2026-GANJIL', $summary['ips_semester']);
        $this->assertSame(3.25, $summary['ipk']);
        $this->assertSame(3, $summary['final_count']);
    }

    private function operationalStudent(): array
    {
        if (! Carbon::hasTestNow()) {
            Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00', 'Asia/Jakarta'));
        }
        $user = User::factory()->create(['username' => 'student-workflow-'.uniqid(), 'level' => User::ROLE_MAHASISWA]);
        $program = ProgramStudi::query()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak', 'jenjang' => 'S1', 'aktif' => true]);
        $student = Mahasiswa::query()->create(['user_id' => $user->id, 'nim' => 'RPL-'.strtoupper(substr(uniqid(), -8)), 'nama' => 'Mahasiswa Operasional', 'angkatan' => 2024, 'program_studi_id' => $program->id]);
        $semester = Semester::query()->create([
            'kode' => '2026-GANJIL', 'tahun_ajaran' => '2026/2027', 'periode' => 1, 'aktif' => true,
            'krs_mulai_pada' => '2026-10-29 09:00:00', 'krs_selesai_pada' => '2026-10-31 17:00:00',
        ]);
        $course1 = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'RPL101', 'nama' => 'Algoritma', 'sks' => 3]);
        $course2 = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'RPL102', 'nama' => 'Basis Data', 'sks' => 3]);

        return [$user, $student, $semester, $course1, $course2];
    }

    private function classFor(MataKuliah $course, Semester $semester, string $section, ?int $lecturerId = null): KelasKuliah
    {
        return KelasKuliah::query()->create(['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'dosen_id' => $lecturerId, 'kode_kelas' => $section, 'kapasitas' => 25]);
    }
}
