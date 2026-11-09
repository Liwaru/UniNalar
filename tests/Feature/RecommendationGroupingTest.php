<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class RecommendationGroupingTest extends TestCase
{
    use RefreshDatabase;

    private function analysis(array $data, ?int $semester, string $date, int $count = 2, string $transport = 'live'): AnalisisRisiko
    {
        $analysis = AnalisisRisiko::create(['mahasiswa_id' => $data['profiles'][0]->id, 'semester_id' => $semester,
            'dianalisis_pada' => $date, 'ringkasan' => ['sumber' => 'groq_deskriptif', 'transport' => $transport]]);
        foreach (range(1, $count) as $i) {
            $title = 'Langkah tersimpan '.$analysis->id.'-'.$i;
            $analysis->rekomendasiIntervensi()->create(['judul' => $title, 'deskripsi' => $title]);
        }

        return $analysis;
    }

    public function test_semesters_sort_chronologically_and_analyses_remain_distinct_with_accurate_sources(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $d = MysqlFixtures::make(2);
        $old = Semester::create(['kode' => 'Z-PERIODE-LAMA', 'tahun_ajaran' => '2025/2026', 'periode' => 1]);
        $next = Semester::create(['kode' => 'A-PERIODE-GENAP', 'tahun_ajaran' => '2025/2026', 'periode' => 2]);
        $newest = $this->analysis($d, $d['semester']->id, '2026-11-09 08:00:00');
        $earlier = $this->analysis($d, $d['semester']->id, '2026-11-08 08:00:00', 2, 'mock');
        $this->analysis($d, $old->id, '2026-11-10 08:00:00');
        $this->analysis($d, $next->id, '2026-11-01 08:00:00');
        $this->analysis($d, null, '2026-11-11 08:00:00');
        $foreign = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][1]->id, 'semester_id' => $d['semester']->id, 'dianalisis_pada' => now()]);
        $foreign->rekomendasiIntervensi()->create(['judul' => 'SECRET-OTHER-STUDENT']);
        $url = route('dashboard.mahasiswa.rekomendasi');
        $response = $this->actingAs($d['users'][0])->get($url)->assertOk()
            ->assertViewHas('recommendationGroups', fn ($g) => $g->keys()->all() === [$d['semester']->id, $next->id, $old->id, 'unassigned'])
            ->assertViewHas('recommendationGroups', fn ($g) => $g[$d['semester']->id]->pluck('id')->all() === [$newest->id, $earlier->id])
            ->assertSee('Groq live')->assertSee('Simulasi pengujian')->assertSee('Semester belum dicatat')
            ->assertDontSee('SECRET-OTHER-STUDENT')->assertDontSee('Direkomendasikan');
        $title = $newest->rekomendasiIntervensi()->first()->judul;
        $this->assertSame(1, substr_count($response->getContent(), $title), 'Identical title and description must render once.');
        $this->get($url.'?semester_id='.$d['semester']->id)->assertOk()
            ->assertViewHas('recommendationGroups', fn ($g) => $g->keys()->all() === [$d['semester']->id])
            ->assertDontSee('data-recommendation-semester="'.$old->id.'"', false);
        $empty = Semester::create(['kode' => 'QA-KOSONG', 'tahun_ajaran' => '2027/2028', 'periode' => 1]);
        $this->get($url.'?semester_id='.$empty->id)->assertOk()->assertSee('Belum ada rekomendasi tersimpan untuk semester yang dipilih');
        Http::assertNothingSent();
    }

    public function test_pagination_keeps_whole_analyses_and_semester_totals_without_duplicates(): void
    {
        $d = MysqlFixtures::make();
        foreach (range(1, 26) as $i) {
            $this->analysis($d, $d['semester']->id, '2026-11-08 08:00:00');
        }
        $large = $this->analysis($d, $d['semester']->id, '2026-11-09 08:00:00', 30);
        $url = route('dashboard.mahasiswa.rekomendasi', ['semester_id' => $d['semester']->id]);
        $first = $this->actingAs($d['users'][0])->get($url)->assertOk()
            ->assertViewHas('analysisPages', fn ($p) => $p->total() === 27 && $p->count() === 25)
            ->assertViewHas('recommendations', fn ($rows) => $rows->count() === 78)
            ->assertSee('82 rekomendasi')->assertSee('27 analisis')->assertSee('semester_id='.$d['semester']->id, false);
        $ids1 = $first->viewData('recommendations')->pluck('id');
        $this->assertCount(30, $first->viewData('analysisPages')->first()->rekomendasiIntervensi);
        $this->assertSame($large->id, $first->viewData('analysisPages')->first()->id);
        $second = $this->get($url.'&page=2')->assertOk()
            ->assertViewHas('analysisPages', fn ($p) => $p->count() === 2)
            ->assertViewHas('recommendations', fn ($rows) => $rows->count() === 4);
        $ids2 = $second->viewData('recommendations')->pluck('id');
        $this->assertCount(0, $ids1->intersect($ids2));
        $this->assertEqualsCanonicalizing(RekomendasiIntervensi::pluck('id')->all(), $ids1->merge($ids2)->all());
    }
}
