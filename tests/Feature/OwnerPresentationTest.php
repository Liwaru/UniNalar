<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\Kehadiran;
use App\Models\Krs;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class OwnerPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributions_count_all_filtered_records_not_only_the_current_page(): void
    {
        Http::preventStrayRequests();
        $d = MysqlFixtures::make(21);
        foreach (range(0, 20) as $index) {
            $detail = MysqlFixtures::detail($d, $index);
            Nilai::create(['krs_detail_id' => $detail->id, 'nilai_huruf' => $index === 20 ? 'B' : 'A', 'nilai_angka' => $index === 20 ? 75 : 90, 'nilai_mutu' => $index === 20 ? 3 : 4, 'final_at' => now()]);
            Kehadiran::create(['krs_detail_id' => $detail->id, 'pertemuan_ke' => 1, 'tanggal' => '2026-11-01', 'status' => $index === 20 ? 'izin' : 'hadir']);
        }
        $this->actingAs(User::factory()->create(['level' => 4]));
        $filters = ['semester_id' => $d['semester']->id, 'program_studi_id' => $d['program']->id, 'page' => 2];
        $this->get(route('dashboard.owner.nilai.index', $filters))->assertOk()->assertSee('data-distribution="grades" data-total="21"', false)->assertSee('data-category="A" data-count="20"', false)->assertSee('data-category="B" data-count="1"', false)->assertViewHas('records', fn ($p) => $p->count() === 1);
        $this->get(route('dashboard.owner.kehadiran.index', $filters))->assertOk()->assertSee('data-distribution="attendance" data-total="21"', false)->assertSee('data-category="hadir" data-count="20"', false)->assertSee('data-category="izin" data-count="1"', false)->assertDontSee('data-category="alpha"', false);
        $this->get(route('dashboard.owner.nilai.index', ['q' => $d['profiles'][20]->nim]))->assertOk()->assertSee('data-distribution="grades" data-total="1"', false)->assertSee('Hanya satu kategori tercatat')->assertDontSee('owner-chart-track', false);
        $this->get(route('dashboard.owner'))->assertOk()->assertSee('data-distribution="krs" data-total="21"', false)->assertSee('data-category="disetujui" data-count="21"', false);
        Http::assertNothingSent();
    }

    public function test_recommendation_disclosure_preserves_text_and_unknown_risk_is_neutral(): void
    {
        Http::preventStrayRequests();
        $d = MysqlFixtures::make();
        $analysis = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $d['semester']->id, 'versi_model' => 'qa-presentation', 'dianalisis_pada' => now(), 'ringkasan' => ['sumber' => 'fixture_terisolasi']]);
        $text = 'Rekomendasi identik QA';
        $recommendation = RekomendasiIntervensi::create(['analisis_risiko_id' => $analysis->id, 'judul' => $text, 'deskripsi' => $text]);
        $this->actingAs(User::factory()->create(['level' => 4]));
        $response = $this->get(route('dashboard.owner.rekomendasi.index'))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), $text));
        $this->assertSame($text, $recommendation->fresh()->deskripsi);
        $this->get(route('dashboard.owner.analisis-risiko.index'))->assertOk()->assertSee('owner-status is-neutral', false)->assertSee('Belum diklasifikasikan')->assertSee('Tidak tercatat');
        $this->get(route('dashboard.owner.tindak-lanjut.index'))->assertOk()->assertSee('Belum ada catatan');
        $this->get(route('dashboard.owner.tindak-lanjut.index', ['q' => 'tidak-ada']))->assertOk()->assertSee('Tidak ada hasil sesuai filter');
        Http::assertNothingSent();
    }

    public function test_krs_distribution_keeps_real_statuses_and_does_not_invent_empty_categories(): void
    {
        Http::preventStrayRequests();
        $d = MysqlFixtures::make(4);
        foreach (['draft', 'menunggu', 'disetujui', 'ditolak'] as $index => $status) {
            Krs::create(['mahasiswa_id' => $d['profiles'][$index]->id, 'semester_id' => $d['semester']->id, 'status' => $status]);
        }
        $this->actingAs(User::factory()->create(['level' => 4]));
        $response = $this->get(route('dashboard.owner'))->assertOk()->assertSee('data-distribution="krs" data-total="4"', false);
        foreach (['draft', 'menunggu', 'disetujui', 'ditolak'] as $status) {
            $response->assertSee('data-category="'.$status.'" data-count="1"', false);
        }
        $this->assertSame(4, substr_count($response->getContent(), 'owner-chart-track'));
        $this->get(route('dashboard.owner.nilai.index'))->assertOk()->assertSee('data-distribution="grades" data-total="0"', false)->assertDontSee('owner-chart-track', false);
        Http::assertNothingSent();
    }
}
