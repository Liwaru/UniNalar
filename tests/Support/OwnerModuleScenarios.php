<?php

namespace Tests\Support;

use App\Models\AnalisisRisiko;
use App\Models\FaktorRisiko;
use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Models\User;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use Illuminate\Support\Facades\DB;

/** Identical acceptance scenarios on SQLite and verified isolated MySQL. */
trait OwnerModuleScenarios
{
    private function ownerPages(): array
    {
        return ['mahasiswa', 'dosen', 'kelas', 'krs', 'nilai', 'kehadiran', 'analisis-risiko', 'rekomendasi', 'tindak-lanjut', 'laporan'];
    }

    public function test_owner_monitoring_pages_are_read_only_and_role_protected(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $analysis = $this->storedAnalysis($d);
        $urls = [route('dashboard.owner', ['program_studi_id' => $d['program']->id])];
        foreach ($this->ownerPages() as $page) {
            $urls[] = route('dashboard.owner.'.$page.'.index');
        }
        foreach (['mahasiswa' => ['student' => $d['profiles'][0]->id], 'dosen' => ['lecturer' => $d['lecturer']->id], 'kelas' => ['classRoom' => $d['class']->id], 'krs' => ['krs' => $detail->krs_id], 'analisis-risiko' => ['analysis' => $analysis->id]] as $page => $parameters) {
            $urls[] = route('dashboard.owner.'.$page.'.show', $parameters);
        }
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach ([$d['users'][0], $d['lecturerUser'], $d['admin']] as $actor) {
            $this->actingAs($actor);
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
        }
        $this->actingAs(User::factory()->create(['level' => 4]));
        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="_method"', false);
            $this->post($url)->assertStatus(405);
        }
        $this->post(route('dashboard.admin-akademik.program-studi.store'), ['kode' => 'QA-FORBIDDEN'])->assertForbidden();
        $this->put(route('dashboard.dosen.nilai.save', $detail->id), ['nilai_angka' => 0])->assertForbidden();
        $this->assertDatabaseMissing('program_studi', ['kode' => 'QA-FORBIDDEN']);
    }

    public function test_owner_filter_validation_search_zero_and_scope_tampering(): void
    {
        $d = MysqlFixtures::make();
        $this->actingAs(User::factory()->create(['level' => 4]));
        foreach ($this->ownerPages() as $page) {
            $url = route('dashboard.owner.'.$page.'.index');
            foreach (['program_studi_id' => [$d['program']->id], 'semester_id' => 999999, 'q' => ['bad']] as $key => $value) {
                $this->getJson($url.'?'.http_build_query([$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
            }
        }
        $d['profiles'][0]->update(['nama' => 'Nama 0 QA']);
        $this->get(route('dashboard.owner.mahasiswa.index', ['q' => ' 0 ', 'program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1);
        $this->get(route('dashboard.owner.mahasiswa.index', ['q' => "' OR 1=1 --"]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 0);
        $foreign = ProgramStudi::create(['kode' => 'QA-EMPTY-SCOPE', 'nama' => 'Program lain', 'jenjang' => 'S1']);
        $this->get(route('dashboard.owner.mahasiswa.show', ['student' => $d['profiles'][0]->id, 'program_studi_id' => $foreign->id]))->assertNotFound();
        $this->getJson(route('dashboard.owner.nilai.index', ['kelas_kuliah_id' => 999999]))->assertUnprocessable();
        $this->getJson(route('dashboard.owner.kehadiran.index', ['program_studi_id' => [$foreign->id]]))->assertUnprocessable();
        $this->get(route('dashboard.owner.kelas.show', ['classRoom' => 'abc']))->assertNotFound();
    }

    public function test_owner_semester_ips_is_filtered_but_ipk_keeps_documented_course_attempt_rule(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_huruf' => 'A', 'nilai_angka' => 90, 'nilai_mutu' => 4, 'final_at' => now()]);
        $semester = Semester::create(['kode' => 'QA-OLDER-GPA', 'tahun_ajaran' => '2025/2026', 'periode' => 2]);
        $class = KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $semester->id, 'kode_kelas' => 'OLD']);
        $krs = Krs::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $old = KrsDetail::create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
        Nilai::create(['krs_detail_id' => $old->id, 'nilai_huruf' => 'B', 'nilai_angka' => 80, 'nilai_mutu' => 3, 'final_at' => now()->subDay()]);
        $owner = User::factory()->create(['level' => 4]);
        $this->actingAs($owner)->get(route('dashboard.owner', ['semester_id' => $semester->id, 'program_studi_id' => $d['program']->id]))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['ips'] === 3.0 && $s['ipk'] === 4.0 && $s['krs'] === 1);
        $this->get(route('dashboard.owner.mahasiswa.index', ['semester_id' => $semester->id, 'program_studi_id' => $d['program']->id]))->assertOk()
            ->assertViewHas('summaries', fn ($s) => $s[$d['profiles'][0]->id]['ips'] === 3.0 && $s[$d['profiles'][0]->id]['ipk'] === 4.0);
        $empty = Semester::create(['kode' => 'QA-NO-GRADE', 'tahun_ajaran' => '2030/2031', 'periode' => 1]);
        $this->get(route('dashboard.owner', ['semester_id' => $empty->id, 'program_studi_id' => $d['program']->id]))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['ips'] === null && $s['ipk'] === 4.0)
            ->assertViewHas('metrics', fn ($m) => $m['final'] === 0 && $m['official'] === 0);
        $this->get(route('dashboard.owner.nilai.index', ['semester_id' => $semester->id, 'program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1 && $p->first()->krsDetail->krs->semester_id === $semester->id);
    }

    public function test_four_role_krs_revisions_grades_and_owner_aggregates_agree(): void
    {
        $d = MysqlFixtures::make();
        $owner = User::factory()->create(['level' => 4]);
        $this->actingAs($d['admin'])->put(route('dashboard.admin-akademik.kelas-kuliah.update', $d['class']->id), ['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'QA-OWNER-CLASS', 'kapasitas' => 30, 'ruang' => 'QA-ROOM'])->assertSessionHasNoErrors();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $history = $service->submit($d['users'][0]);
        $this->actingAs($owner)->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('krsStatuses', fn ($s) => $s['menunggu'] === 1)->assertViewHas('metrics', fn ($m) => $m['official'] === 0);
        app(KrsDecisionService::class)->decide($history->id, $d['admin'], 'ditolak', 'QA perbaiki');
        $original = $d['course']->nama;
        $d['course']->update(['nama' => 'Nama baru QA']);
        $version2 = $service->submit($d['users'][0]);
        app(KrsDecisionService::class)->decide($version2->id, $d['admin'], 'disetujui', 'QA disetujui');
        $detail = $history->krs->details()->firstOrFail();
        $grade = app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 91, 'nilai_huruf' => 'A']);
        $this->actingAs($owner)->get(route('dashboard.owner.nilai.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 0);
        $this->get(route('dashboard.owner.kelas.show', $d['class']->id))->assertOk()->assertSee('Belum final')->assertDontSee('91.00');
        app(GradeFinalizationService::class)->finalize($d['lecturerUser'], $grade->id);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('gradeSummary', fn ($s) => $s['ipk'] === 4.0);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.peserta', $d['class']->id))->assertOk()->assertSee($d['profiles'][0]->nim);
        $this->actingAs($owner)->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('metrics', fn ($m) => $m['final'] === 1 && $m['official'] === 1)->assertViewHas('krsStatuses', fn ($s) => $s['disetujui'] === 1);
        $this->get(route('dashboard.owner.krs.show', $history->krs_id))->assertOk()->assertSee($original)->assertSee('Nama baru QA')->assertSee('Versi 1')->assertSee('Versi 2')->assertSee('QA perbaiki')->assertDontSee('>Setujui<', false);
        $this->get(route('dashboard.owner.laporan.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->first()->students_count === 1 && $p->first()->approved_count === 1 && $p->first()->classes_count === 1 && $p->first()->disetujui_count === 1 && $p->first()->final_count === 1);
    }

    public function test_owner_official_roster_and_attendance_exclude_pending_rejected_and_dropped(): void
    {
        $d = MysqlFixtures::make(4);
        $approved = MysqlFixtures::detail($d);
        $pending = MysqlFixtures::detail($d, 1, 'menunggu');
        $rejected = MysqlFixtures::detail($d, 2, 'ditolak');
        $dropped = MysqlFixtures::detail($d, 3);
        $dropped->update(['status' => 'dibatalkan']);
        foreach ([$approved, $pending, $rejected, $dropped] as $detail) {
            Kehadiran::create(['krs_detail_id' => $detail->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-01', 'status' => 'hadir']);
        }
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner.kelas.show', $d['class']->id))->assertOk()->assertViewHas('record', fn ($c) => $c->official_count === 1)->assertViewHas('records', fn ($p) => $p->total() === 1);
        $this->get(route('dashboard.owner.kehadiran.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('counts', fn ($c) => (int) $c['hadir'] === 1)->assertViewHas('records', fn ($p) => $p->total() === 1);
    }

    public function test_owner_stored_results_filter_provenance_and_do_not_disclose_foreign_factor_or_private_notes(): void
    {
        $d = MysqlFixtures::make(2);
        $own = $this->storedAnalysis($d);
        $foreign = $this->storedAnalysis($d, 1);
        $factor = FaktorRisiko::create(['analisis_risiko_id' => $foreign->id, 'kode_faktor' => 'QA', 'nama' => 'FOREIGN-FACTOR-SECRET', 'penjelasan' => 'FOREIGN-EXPLANATION-SECRET']);
        $rec = RekomendasiIntervensi::create(['analisis_risiko_id' => $own->id, 'faktor_risiko_id' => $factor->id, 'judul' => 'Rekomendasi tersimpan QA']);
        TindakLanjut::create(['rekomendasi_intervensi_id' => $rec->id, 'catatan' => 'INTERNAL-NOTE-SECRET', 'status' => 'direncanakan']);
        $demo = $this->storedAnalysis($d);
        $demo->update(['versi_model' => 'demo-simulation', 'kategori' => 'DEMO-SECRET']);
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner.analisis-risiko.index', ['q' => $d['profiles'][0]->nim]))->assertOk()->assertDontSee('DEMO-SECRET')->assertViewHas('records', fn ($p) => $p->total() === 1);
        $this->get(route('dashboard.owner.analisis-risiko.show', $demo->id))->assertNotFound();
        $this->get(route('dashboard.owner.analisis-risiko.show', $own->id))->assertOk()->assertSee('fixture_terisolasi')->assertDontSee('FOREIGN-FACTOR-SECRET');
        $this->get(route('dashboard.owner.rekomendasi.index', ['q' => $d['profiles'][0]->nim]))->assertOk()->assertSee('Rekomendasi tersimpan QA')->assertDontSee('FOREIGN-EXPLANATION-SECRET');
        $this->get(route('dashboard.owner.tindak-lanjut.index'))->assertOk()->assertDontSee('INTERNAL-NOTE-SECRET');
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.rekomendasi'))->assertOk()->assertSee('Rekomendasi tersimpan QA')->assertDontSee('FOREIGN-EXPLANATION-SECRET');
    }

    public function test_owner_tables_paginate_and_summaries_use_all_filtered_rows(): void
    {
        $d = MysqlFixtures::make(21);
        foreach (range(0, 20) as $index) {
            $detail = MysqlFixtures::detail($d, $index);
            Nilai::create(['krs_detail_id' => $detail->id, 'nilai_huruf' => 'A', 'nilai_angka' => 90, 'nilai_mutu' => 4, 'final_at' => now()]);
            Kehadiran::create(['krs_detail_id' => $detail->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-01', 'status' => 'hadir']);
            $analysis = $this->storedAnalysis($d, $index);
            $rec = RekomendasiIntervensi::create(['analisis_risiko_id' => $analysis->id, 'judul' => 'QA pagination']);
            TindakLanjut::create(['rekomendasi_intervensi_id' => $rec->id]);
        }
        $this->actingAs(User::factory()->create(['level' => 4]));
        foreach (['mahasiswa', 'krs', 'nilai', 'kehadiran', 'analisis-risiko', 'rekomendasi', 'tindak-lanjut'] as $page) {
            $url = route('dashboard.owner.'.$page.'.index', ['semester_id' => $d['semester']->id, 'program_studi_id' => $d['program']->id]);
            $this->get($url)->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 21 && $p->count() === 20)->assertSee('semester_id='.$d['semester']->id, false)->assertSee('Menampilkan')->assertSee('Berikutnya');
            $this->get($url.'&page=2')->assertOk()->assertViewHas('records', fn ($p) => $p->count() === 1);
        }
        $this->get(route('dashboard.owner.kehadiran.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('counts', fn ($c) => (int) $c['hadir'] === 21);
        $this->get(route('dashboard.owner.nilai.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('distribution', fn ($c) => (int) $c['A'] === 21);
    }

    public function test_owner_escaped_metadata_and_minimal_personal_fields(): void
    {
        $d = MysqlFixtures::make();
        $payload = '<script>alert("owner-qa")</script>';
        $d['profiles'][0]->update(['nama' => $payload, 'email' => 'private-owner-qa@example.test', 'no_telepon' => 'PRIVATE-PHONE']);
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner.mahasiswa.show', $d['profiles'][0]->id))->assertOk()->assertSee($payload)->assertDontSee($payload, false)->assertDontSee('private-owner-qa@example.test')->assertDontSee('PRIVATE-PHONE');
        $analysis = $this->storedAnalysis($d);
        $analysis->update(['ringkasan' => ['sumber' => $payload, 'secret' => 'PRIVATE-JSON']]);
        $this->get(route('dashboard.owner.analisis-risiko.show', $analysis->id))->assertOk()->assertSee($payload)->assertDontSee($payload, false)->assertDontSee('PRIVATE-JSON');
    }

    public function test_owner_student_and_class_queries_do_not_grow_per_rendered_row(): void
    {
        $d = MysqlFixtures::make(21);
        foreach (range(0, 20) as $index) {
            MysqlFixtures::detail($d, $index);
        }
        $this->actingAs(User::factory()->create(['level' => 4]));
        foreach ([route('dashboard.owner.mahasiswa.index', ['program_studi_id' => $d['program']->id]), route('dashboard.owner.kelas.show', ['classRoom' => $d['class']->id, 'program_studi_id' => $d['program']->id])] as $url) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get($url.(str_contains($url, '?') ? '&' : '?').'page=2')->assertOk();
            $one = count(DB::getQueryLog());
            DB::flushQueryLog();
            $this->get($url)->assertOk();
            $twenty = count(DB::getQueryLog());
            DB::disableQueryLog();
            $this->assertLessThanOrEqual($one + 3, $twenty, 'Rendering 20 rows must not add a query per student.');
        }
    }

    public function test_owner_program_reports_and_class_counts_handle_cross_program_enrollment(): void
    {
        $d = MysqlFixtures::make();
        $other = ProgramStudi::create(['kode' => 'QA-CROSS', 'nama' => 'Program lintas QA', 'jenjang' => 'S1']);
        $d['profiles'][0]->update(['program_studi_id' => $other->id]);
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        $this->actingAs(User::factory()->create(['level' => 4]));
        $this->get(route('dashboard.owner.kelas.index', ['program_studi_id' => $d['program']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->first()->official_count === 1 && $p->first()->final_count === 1);
        $this->get(route('dashboard.owner.laporan.index'))->assertOk()->assertViewHas('records', function ($p) use ($d, $other) {
            $original = $p->firstWhere('id', $d['program']->id);
            $cross = $p->firstWhere('id', $other->id);

            return $original->students_count === 0 && $original->classes_count === 1 && $original->final_count === 0
                && $cross->students_count === 1 && $cross->classes_count === 0 && $cross->final_count === 1;
        });
        $this->get(route('dashboard.owner.nilai.index', ['program_studi_id' => $other->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1);
        $this->get(route('dashboard.owner.nilai.index', ['program_studi_id' => $other->id, 'kelas_kuliah_id' => $d['class']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1);
        $d['course']->update(['program_studi_id' => $other->id]);
        $this->get(route('dashboard.owner.dosen.index', ['program_studi_id' => $d['program']->id, 'semester_id' => $d['semester']->id]))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 1 && $p->first()->kelas_kuliah_count === 1);
    }

    private function storedAnalysis(array $d, int $student = 0): AnalisisRisiko
    {
        return AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][$student]->id, 'semester_id' => $d['semester']->id, 'skor' => .2, 'kategori' => 'kategori-qa', 'versi_model' => 'qa-stored-v1', 'ringkasan' => ['sumber' => 'fixture_terisolasi'], 'dianalisis_pada' => now()]);
    }
}
