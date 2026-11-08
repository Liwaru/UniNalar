<?php

namespace Tests\Support;

use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\User;
use App\Services\GradeEntryService;
use App\Services\KrsSubmissionService;

/** Synthetic acceptance scenarios shared by SQLite and isolated MySQL. */
trait LecturerModuleScenarios
{
    public function test_class_metadata_is_escaped_in_headers_and_tables(): void
    {
        $d = MysqlFixtures::make();
        $payload = '<script>alert("qa")</script>';
        $d['course']->update(['nama' => $payload]);
        $this->actingAs($d['lecturerUser']);
        foreach (['kelas.show', 'kelas.peserta', 'kelas.kehadiran'] as $route) {
            $this->get(route('dashboard.dosen.'.$route, $d['class']->id))->assertOk()
                ->assertSee($payload)->assertDontSee($payload, false);
        }
        $this->get(route('dashboard.dosen.kelas.index', ['q' => "' OR 1=1 --"]))->assertOk()
            ->assertViewHas('classes', fn ($p) => $p->total() === 0);
    }

    public function test_semester_filters_match_dashboard_catalog_and_grading_context(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        $semester = Semester::create(['kode' => 'QA-PAST-LECTURER', 'tahun_ajaran' => '2025/2026', 'periode' => 2]);
        $class = KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $semester->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'PAST']);
        $krs = Krs::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $detail = KrsDetail::create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen', ['semester_id' => $semester->id]))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['classes'] === 1 && $s['final'] === 1 && $s['pending'] === 0);
        $this->get(route('dashboard.dosen.kelas.index', ['semester_id' => $semester->id]))->assertOk()->assertViewHas('classes', fn ($p) => $p->total() === 1 && $p->first()->id === $class->id);
        $this->get(route('dashboard.dosen.nilai', ['semester_id' => $semester->id]))->assertOk()->assertViewHas('selectedClass', fn ($c) => $c->id === $class->id)->assertViewHas('participants', fn ($p) => $p->total() === 1);
        $this->getJson(route('dashboard.dosen.nilai', ['semester_id' => $semester->id, 'kelas_id' => $d['class']->id]))->assertUnprocessable()->assertJsonValidationErrors('kelas_id');
        $this->getJson(route('dashboard.dosen.kelas.peserta', ['kelas' => $d['class']->id, 'semester_id' => $semester->id]))->assertUnprocessable()->assertJsonValidationErrors('semester_id');
    }

    public function test_lecturer_class_catalog_search_pagination_and_foreign_scope(): void
    {
        $d = MysqlFixtures::make();
        foreach (range(1, 15) as $number) {
            KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'QA-'.$number]);
        }
        $other = MysqlFixtures::make();
        $other['class']->update(['kode_kelas' => 'PRIVATE-OTHER-CLASS']);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.index'))->assertOk()
            ->assertViewHas('classes', fn ($p) => $p->total() === 16 && $p->count() === 15)->assertDontSee('PRIVATE-OTHER-CLASS');
        $url = route('dashboard.dosen.kelas.index', ['semester_id' => $d['semester']->id]);
        $this->get($url.'&page=2')->assertOk()->assertViewHas('classes', fn ($p) => $p->count() === 1)->assertSee('semester_id='.$d['semester']->id, false);
        $this->get(route('dashboard.dosen.kelas.index', ['q' => 'QA-15']))->assertOk()->assertViewHas('classes', fn ($p) => $p->total() === 1);
        $this->get(route('dashboard.dosen.kelas.index', ['q' => 'no-such-class']))->assertOk()->assertSee('Kelas tidak ditemukan');
        foreach (['kelas.show', 'kelas.peserta', 'kelas.kehadiran'] as $route) {
            $this->get(route('dashboard.dosen.'.$route, $other['class']->id))->assertNotFound();
        }
        foreach (['semester_id' => 999999, 'kelas_id' => $other['class']->id, 'q' => ['bad'], 'status_nilai' => 'unknown'] as $key => $value) {
            $this->getJson(route('dashboard.dosen.nilai', [$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->get('/dashboard/dosen/kelas/not-numeric')->assertNotFound();
    }

    public function test_official_roster_search_grade_filters_and_participant_pagination(): void
    {
        $d = MysqlFixtures::make(28);
        foreach (range(0, 27) as $index) {
            $detail = MysqlFixtures::detail($d, $index, $index < 26 ? 'disetujui' : ($index === 26 ? 'menunggu' : 'ditolak'));
            if ($index < 2) {
                Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 80, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => $index === 1 ? now() : null]);
            }
        }
        $url = route('dashboard.dosen.kelas.peserta', $d['class']->id);
        $this->actingAs($d['lecturerUser'])->get($url)->assertOk()->assertViewHas('participants', fn ($p) => $p->total() === 26 && $p->count() === 25)
            ->assertDontSee($d['profiles'][26]->nim)->assertDontSee($d['profiles'][27]->nim);
        $this->get($url.'?page=2')->assertOk()->assertViewHas('participants', fn ($p) => $p->count() === 1);
        $this->get($url.'?q='.$d['profiles'][0]->nim)->assertOk()->assertViewHas('participants', fn ($p) => $p->total() === 1);
        foreach (['draft' => 1, 'final' => 1, 'belum_dinilai' => 24] as $status => $count) {
            $this->get($url.'?status_nilai='.$status)->assertOk()->assertViewHas('participants', fn ($p) => $p->total() === $count);
        }
        $this->get(route('dashboard.dosen.kelas.show', $d['class']->id))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['participants'] === 26 && $s['final'] === 1 && $s['ungraded'] === 24);
    }

    public function test_lecturer_dashboard_counts_unique_students_and_assigned_class_statuses(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $course = MataKuliah::create(['program_studi_id' => $d['program']->id, 'kode' => $d['course']->kode.'-TWO', 'nama' => 'Mata Kuliah Kedua QA', 'sks' => 3]);
        $second = KelasKuliah::create(['mata_kuliah_id' => $course->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'SECOND']);
        KrsDetail::create(['krs_id' => $detail->krs_id, 'kelas_kuliah_id' => $second->id]);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        Kehadiran::create(['krs_detail_id' => $detail->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-01', 'status' => 'hadir']);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen'))->assertOk()->assertSee($d['lecturer']->nama)
            ->assertViewHas('stats', fn ($s) => $s['classes'] === 2 && $s['students'] === 1 && $s['final'] === 1 && $s['pending'] === 1 && $s['ungraded'] === 1 && $s['attendance'] === 1);
    }

    public function test_lecturer_contact_update_is_scoped_and_cannot_change_academic_fields(): void
    {
        $d = MysqlFixtures::make();
        $other = MysqlFixtures::make();
        $other['lecturer']->update(['email' => 'other-contact@qa.example.test']);
        $url = route('dashboard.dosen.profil.update');
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.profil'))->assertOk()->assertSee($d['lecturer']->nama);
        $this->put($url, ['email' => 'updated-contact@qa.example.test', 'no_telepon' => '080000000001', 'nidn' => 'FORGED', 'user_id' => $other['lecturerUser']->id, 'program_studi_id' => $other['program']->id, 'aktif' => false, 'level' => 4])->assertSessionHasNoErrors();
        $profile = $d['lecturer']->fresh();
        $this->assertSame('updated-contact@qa.example.test', $profile->email);
        $this->assertSame($d['lecturerUser']->id, $profile->user_id);
        $this->assertSame($d['program']->id, $profile->program_studi_id);
        $this->assertTrue($profile->aktif);
        $this->assertNull($profile->nidn);
        foreach (['not-an-email', 'reserved@demo.uninlar.test', 'other-contact@qa.example.test'] as $invalid) {
            $this->put($url, ['email' => $invalid])->assertSessionHasErrors('email');
        }
        $this->put($url, ['no_telepon' => str_repeat('1', 31)])->assertSessionHasErrors('no_telepon');
        $this->assertSame('other-contact@qa.example.test', $other['lecturer']->fresh()->email);
    }

    public function test_all_lecturer_read_routes_and_contact_route_enforce_role_and_login(): void
    {
        $d = MysqlFixtures::make();
        $routes = [route('dashboard.dosen'), route('dashboard.dosen.kelas.index'), route('dashboard.dosen.kelas.show', $d['class']->id), route('dashboard.dosen.kelas.peserta', $d['class']->id), route('dashboard.dosen.kelas.kehadiran', $d['class']->id), route('dashboard.dosen.nilai'), route('dashboard.dosen.profil')];
        foreach ($routes as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach ([1, 3, 4] as $level) {
            $this->actingAs(User::factory()->create(['level' => $level]));
            foreach ($routes as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->put(route('dashboard.dosen.profil.update'), ['email' => 'forged@qa.example.test'])->assertForbidden();
        }
        $unlinked = User::factory()->create(['level' => 2]);
        $this->actingAs($unlinked)->get(route('dashboard.dosen.kelas.index'))->assertOk()->assertSee('Profil dosen belum tertaut');
        $this->put(route('dashboard.dosen.profil.update'), [])->assertNotFound();
        $this->get(route('dashboard.dosen.kelas.show', $d['class']->id))->assertNotFound();
    }

    public function test_grade_forms_are_bounded_to_twenty_five_participants_and_keep_filter(): void
    {
        $d = MysqlFixtures::make(26);
        foreach (range(0, 25) as $index) {
            MysqlFixtures::detail($d, $index);
        }
        $url = route('dashboard.dosen.nilai', ['kelas_id' => $d['class']->id]);
        $response = $this->actingAs($d['lecturerUser'])->get($url)->assertOk()
            ->assertViewHas('participants', fn ($p) => $p->total() === 26 && $p->count() === 25);
        $this->assertSame(25, substr_count($response->getContent(), 'class="grade-entry-form"'));
        $this->get($url.'&page=2')->assertOk()->assertViewHas('participants', fn ($p) => $p->count() === 1)->assertSee('kelas_id='.$d['class']->id, false);
    }

    public function test_grade_validation_boundaries_revision_lock_and_cross_role_integration(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $url = route('dashboard.dosen.nilai.save', $detail->id);
        $this->actingAs($d['lecturerUser']);
        foreach (['', -1, 101, 'bad', '80.001', ['bad']] as $invalid) {
            $this->put($url, ['nilai_angka' => $invalid, 'nilai_huruf' => 'A'])->assertSessionHasErrors('nilai_angka');
        }
        $this->put($url, ['nilai_angka' => 0, 'nilai_huruf' => 'E'])->assertSessionHasNoErrors();
        $grade = Nilai::where('krs_detail_id', $detail->id)->firstOrFail();
        $this->assertSame('0.00', $grade->nilai_angka);
        $revision = GradeEntryService::revision($grade);
        $this->put($url, ['nilai_angka' => 100, 'nilai_huruf' => 'A', 'revision' => $revision])->assertSessionHasNoErrors();
        $this->put($url, ['nilai_angka' => 99, 'nilai_huruf' => 'A', 'revision' => $revision])->assertSessionHasErrors('nilai');
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('rataRataNilai', null);
        $this->actingAs($d['lecturerUser'])->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasNoErrors();
        $this->put($url, ['nilai_angka' => 1, 'nilai_huruf' => 'E'])->assertSessionHasErrors('nilai');
        $this->post(route('dashboard.dosen.nilai.finalize', $grade->id))->assertSessionHasErrors('nilai');
        $this->assertSame('100.00', $grade->fresh()->nilai_angka);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('gradeSummary', fn ($s) => $s['ipk'] === 4.0);
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('stats', fn ($s) => $s['ipk'] === 4.0);
    }

    public function test_admin_class_assignment_and_krs_approval_reach_lecturer_roster(): void
    {
        $d = MysqlFixtures::make();
        $payload = ['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'ASSIGNED-QA', 'kapasitas' => 10];
        $this->actingAs($d['admin'])->post(route('dashboard.admin-akademik.kelas-kuliah.store'), $payload)->assertSessionHasNoErrors();
        $class = KelasKuliah::where('kode_kelas', 'ASSIGNED-QA')->where('dosen_id', $d['lecturer']->id)->firstOrFail();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $class->id);
        $history = $service->submit($d['users'][0]);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.peserta', $class->id))->assertOk()->assertViewHas('participants', fn ($p) => $p->total() === 0);
        $this->actingAs($d['admin'])->post(route('dashboard.admin-akademik.krs-pengajuan.decision', [$history->id, 'disetujui']))->assertSessionHasNoErrors();
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.peserta', $class->id))->assertOk()->assertSee($d['profiles'][0]->nim);
    }

    public function test_read_only_attendance_is_scoped_and_summarizes_all_pages(): void
    {
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        $pending = MysqlFixtures::detail($d, 1, 'menunggu');
        foreach (range(1, 26) as $meeting) {
            Kehadiran::create(['krs_detail_id' => $detail->id, 'pertemuan_ke' => $meeting, 'tanggal' => '2026-11-01', 'status' => 'hadir']);
        }
        Kehadiran::create(['krs_detail_id' => $pending->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-02', 'status' => 'PRIVATE-PENDING']);
        $url = route('dashboard.dosen.kelas.kehadiran', $d['class']->id);
        $this->actingAs($d['lecturerUser'])->get($url)->assertOk()->assertDontSee('PRIVATE-PENDING')
            ->assertViewHas('statuses', fn ($s) => (int) $s['hadir'] === 26)->assertViewHas('records', fn ($p) => $p->total() === 26 && $p->count() === 25);
        $this->get($url.'?page=2')->assertOk()->assertViewHas('records', fn ($p) => $p->count() === 1);
        $this->get($url.'?pertemuan_ke=1')->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1);
        $this->getJson($url.'?pertemuan_ke=0')->assertUnprocessable();
        $this->post($url, [])->assertStatus(405);
    }
}
