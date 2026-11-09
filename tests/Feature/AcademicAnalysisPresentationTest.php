<?php

namespace Tests\Feature;

use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Nilai;
use App\Models\Semester;
use App\Services\AcademicGradeSummary;
use App\Services\GroqAcademicAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class AcademicAnalysisPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_history_scopes_published_grades_and_never_uses_future_semesters_in_past_ipk(): void
    {
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        $grade = Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 80, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => now()]);
        $other = MysqlFixtures::detail($d, 1);
        Nilai::create(['krs_detail_id' => $other->id, 'nilai_angka' => 95, 'nilai_huruf' => 'A', 'nilai_mutu' => 4, 'final_at' => now()]);
        $past = Semester::create(['kode' => '2025-GENAP', 'tahun_ajaran' => '2025/2026', 'periode' => 2]);
        $class = KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $past->id, 'kode_kelas' => 'PAST']);
        $krs = Krs::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $past->id, 'status' => 'disetujui']);
        $pastDetail = KrsDetail::create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
        Nilai::create(['krs_detail_id' => $pastDetail->id, 'nilai_angka' => 60, 'nilai_huruf' => 'C', 'nilai_mutu' => 2, 'final_at' => now()->subYear()]);
        $service = app(AcademicGradeSummary::class);
        $history = $service->historyForStudent($d['profiles'][0])->all();
        $this->assertSame([
            ['semester' => '2025-GENAP', 'ips' => 2.0, 'ipk' => 2.0],
            ['semester' => '2026-GANJIL', 'ips' => 3.0, 'ipk' => 3.0],
        ], $history);
        // Fixture-only transition to verify draft exclusion; no production edit flow is invoked.
        Nilai::whereKey($grade->id)->update(['final_at' => null]);
        $this->assertSame([$history[0]], $service->historyForStudent($d['profiles'][0])->all());
        $krs->update(['status' => 'ditolak']);
        $this->assertTrue($service->historyForStudent($d['profiles'][0])->isEmpty());
    }

    public function test_historical_cards_and_current_chart_remain_separate_from_ai_prose(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 80, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => now()]);
        config(['services.groq.key' => 'synthetic-test-only', 'services.groq.live_enabled' => false]);
        Http::preventStrayRequests();
        $prose = 'Teks tersimpan menyebut IPS 1,23; angka teks ini tidak boleh menjadi data grafik.';
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
            'penjelasan' => $prose, 'rekomendasi' => ['Tinjau materi kuliah.'],
        ])]]]])]);
        $analysis = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        // Model a different current record without altering the saved analysis snapshot.
        Nilai::whereKey($grade->id)->update(['nilai_angka' => 95, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.analisis-risiko'))
            ->assertOk()->assertSee($prose)->assertSee('3,00')->assertSee('Saat analisis')
            ->assertSee('analysis-recommendations', false)
            ->assertViewHas('academicHistory', fn ($rows) => $rows->first()['ips'] === 4.0 && $rows->first()['ipk'] === 4.0);
        $this->assertSame(3, $analysis->fresh()->ringkasan['indikator']['ips_semester']);
        $summary = $analysis->ringkasan;
        unset($summary['indikator']['ips_semester'], $summary['indikator']['ipk_kumulatif']);
        $analysis->update(['ringkasan' => $summary]);
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertSee('Data belum tersedia');
        Http::assertSentCount(1);
    }
}
