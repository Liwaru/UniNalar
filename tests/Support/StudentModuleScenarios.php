<?php

namespace Tests\Support;

use App\Models\AnalisisRisiko;
use App\Models\FaktorRisiko;
use App\Models\Kehadiran;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;

/** Same student acceptance scenarios on SQLite and isolated MySQL. */
trait StudentModuleScenarios
{
    public function test_student_krs_history_survives_closed_or_missing_active_semester(): void
    {
        $d = MysqlFixtures::make();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $history = $service->submit($d['users'][0]);
        app(KrsDecisionService::class)->decide($history->id, $d['admin'], 'ditolak', 'Revisi QA tersimpan');
        $d['semester']->update(['aktif' => false]);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.krs'))->assertOk()
            ->assertSee('Semester operasional aktif belum tersedia')->assertSee('Riwayat pengajuan')
            ->assertSee('Revisi QA tersimpan')->assertSee($d['course']->nama)
            ->assertViewHas('histories', fn ($items) => $items->count() === 1);
        $this->post(route('dashboard.mahasiswa.krs.submit'))->assertNotFound();
        $this->assertSame(1, $history->krs->riwayatPengajuan()->count());
    }

    public function test_student_dashboard_and_grade_page_agree_on_credits_and_final_gpa(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()
            ->assertViewHas('jumlahSksAktif', 3)->assertViewHas('jumlahMataKuliahAktif', 1)
            ->assertViewHas('activeKrs', fn ($krs) => $krs->status === 'disetujui')
            ->assertViewHas('gradeSummary', fn ($summary) => $summary['ipk'] === 4.0);
        $this->get(route('dashboard.mahasiswa.nilai'))->assertOk()->assertSee('IPS dan IPK')
            ->assertViewHas('gradeSummary', fn ($summary) => $summary['ips'] === 4.0 && $summary['ipk'] === 4.0)
            ->assertSee('Final');
        $empty = Semester::create(['kode' => 'QA-EMPTY-GRADES', 'tahun_ajaran' => '2030/2031', 'periode' => 1]);
        $this->get(route('dashboard.mahasiswa.nilai', ['semester_id' => $empty->id]))->assertOk()
            ->assertViewHas('nilai', fn ($p) => $p->total() === 0)
            ->assertViewHas('gradeSummary', fn ($summary) => $summary['ipk'] === 4.0);
    }

    public function test_student_attendance_summary_uses_all_pages_and_only_approved_enrollments(): void
    {
        $d = MysqlFixtures::make(2);
        $own = MysqlFixtures::detail($d);
        $other = MysqlFixtures::detail($d, 1);
        foreach (range(1, 26) as $meeting) {
            Kehadiran::create(['krs_detail_id' => $own->id, 'pertemuan_ke' => $meeting, 'tanggal' => '2026-11-01', 'status' => $meeting === 26 ? 'alpa' : 'hadir']);
        }
        Kehadiran::create(['krs_detail_id' => $other->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-02', 'status' => 'SECRET-OTHER-STATUS']);
        $url = route('dashboard.mahasiswa.kehadiran', ['semester_id' => $d['semester']->id]);
        $this->actingAs($d['users'][0])->get($url)->assertOk()->assertDontSee('SECRET-OTHER-STATUS')
            ->assertViewHas('kehadiran', fn ($p) => $p->total() === 26 && $p->count() === 25)
            ->assertViewHas('attendanceSummary', fn ($s) => $s['validCount'] === 26 && $s['percentage'] === 96.2)
            ->assertSee('semester_id='.$d['semester']->id, false);
        $this->get($url.'&page=2')->assertOk()->assertViewHas('kehadiran', fn ($p) => $p->count() === 1)
            ->assertViewHas('attendanceSummary', fn ($s) => $s['validCount'] === 26);
        $this->get(route('dashboard.mahasiswa'))->assertOk()
            ->assertViewHas('attendanceSummary', fn ($s) => $s['percentage'] === 96.2);
        $own->krs->update(['status' => 'ditolak']);
        $this->get($url)->assertOk()->assertViewHas('kehadiran', fn ($p) => $p->total() === 0)
            ->assertViewHas('attendanceSummary', fn ($s) => $s['percentage'] === null);
    }

    public function test_student_result_filters_pagination_provenance_and_ownership(): void
    {
        $d = MysqlFixtures::make(2);
        foreach (range(1, 26) as $number) {
            $analysis = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $d['semester']->id, 'skor' => .2, 'kategori' => 'kategori-qa', 'versi_model' => 'qa-stored-v1', 'ringkasan' => ['sumber' => 'fixture_terisolasi'], 'dianalisis_pada' => now()]);
            $recommendation = RekomendasiIntervensi::create(['analisis_risiko_id' => $analysis->id, 'judul' => 'Rekomendasi pemilik QA']);
            TindakLanjut::create(['rekomendasi_intervensi_id' => $recommendation->id, 'status' => 'direncanakan', 'catatan' => 'PRIVATE-QA-NOTE']);
        }
        $foreign = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][1]->id, 'semester_id' => $d['semester']->id, 'skor' => .9, 'kategori' => 'SECRET-OTHER-RISK', 'dianalisis_pada' => now()]);
        $rec = RekomendasiIntervensi::create(['analisis_risiko_id' => $foreign->id, 'judul' => 'SECRET-OTHER-RECOMMENDATION']);
        TindakLanjut::create(['rekomendasi_intervensi_id' => $rec->id, 'status' => 'SECRET-OTHER-FOLLOW']);
        $this->actingAs($d['users'][0]);
        // Recommendations now paginate whole analyses; keep the same ownership and page-size assertions.
        foreach (['analisis-risiko' => 'analyses', 'rekomendasi' => 'analysisPages', 'tindak-lanjut' => 'followUps'] as $page => $variable) {
            $url = route('dashboard.mahasiswa.'.$page, ['semester_id' => $d['semester']->id]);
            $this->get($url)->assertOk()->assertViewHas($variable, fn ($p) => $p->total() === 26 && $p->count() === 25)
                ->assertDontSee('SECRET-OTHER-')->assertDontSee('PRIVATE-QA-NOTE');
            $this->get($url.'&page=2')->assertOk()->assertViewHas($variable, fn ($p) => $p->count() === 1);
        }
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertSee('qa-stored-v1')->assertSee('fixture_terisolasi');
        $this->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('recommendationCount', 26)->assertViewHas('followUpCount', 26);
    }

    public function test_recommendation_cannot_disclose_a_factor_from_another_analysis(): void
    {
        $d = MysqlFixtures::make(2);
        $own = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][0]->id, 'skor' => .2, 'kategori' => 'qa', 'dianalisis_pada' => now()]);
        $other = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][1]->id, 'skor' => .3, 'kategori' => 'qa', 'dianalisis_pada' => now()]);
        $factor = FaktorRisiko::create(['analisis_risiko_id' => $other->id, 'kode_faktor' => 'QA', 'nama' => 'QA', 'penjelasan' => 'SECRET-FOREIGN-FACTOR']);
        RekomendasiIntervensi::create(['analisis_risiko_id' => $own->id, 'faktor_risiko_id' => $factor->id, 'judul' => 'Rekomendasi QA']);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.rekomendasi'))->assertOk()
            ->assertSee('Alasan rekomendasi belum dicatat')->assertDontSee('SECRET-FOREIGN-FACTOR');
    }

    public function test_student_filters_reject_unknown_demo_and_array_semesters(): void
    {
        $d = MysqlFixtures::make();
        $demo = Semester::create(['kode' => 'DEMO-QA-FILTER', 'tahun_ajaran' => '2031/2032', 'periode' => 1]);
        $this->actingAs($d['users'][0]);
        foreach (['nilai', 'kehadiran', 'analisis-risiko', 'rekomendasi', 'tindak-lanjut'] as $page) {
            foreach ([999999, [$d['semester']->id], 'invalid', $demo->id] as $invalid) {
                $this->getJson(route('dashboard.mahasiswa.'.$page, ['semester_id' => $invalid]))
                    ->assertUnprocessable()->assertJsonValidationErrors('semester_id');
            }
        }
        $this->delete('/dashboard/mahasiswa/krs/detail/not-a-number')->assertNotFound();
    }

    public function test_full_class_is_labelled_and_server_rejects_forged_selection(): void
    {
        $d = MysqlFixtures::make(2, 1);
        MysqlFixtures::detail($d, 1, 'menunggu');
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.krs'))->assertOk()
            ->assertSee('Kelas penuh')->assertViewHas('availableClasses', fn ($items) => $items->first()->reserved_places === 1);
        $this->post(route('dashboard.mahasiswa.krs.kelas.store'), ['kelas_kuliah_id' => $d['class']->id])
            ->assertSessionHasErrors('kelas_kuliah_id');
        $this->assertDatabaseMissing('krs', ['mahasiswa_id' => $d['profiles'][0]->id]);
    }
}
