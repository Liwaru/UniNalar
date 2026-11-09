<?php

namespace Tests\Feature;

use App\Models\KelasKuliah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class AdminManagementPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_total_uses_filtered_query_across_pagination(): void
    {
        Http::preventStrayRequests();
        $d = MysqlFixtures::make();
        foreach (range(1, 16) as $number) {
            KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'kode_kelas' => 'PRESENTASI-'.$number]);
        }
        $this->actingAs($d['admin']);
        $url = route('dashboard.admin-akademik.kelas-kuliah.index');
        $this->get($url.'?q=PRESENTASI&page=2')->assertOk()->assertSee('<strong>16</strong> kelas kuliah ditemukan', false)->assertSee('PRESENTASI')->assertSee('q=PRESENTASI', false);
        $this->get($url.'?q=tidak-ada-hasil')->assertOk()->assertSee('<strong>0</strong> kelas kuliah ditemukan', false)->assertSee('Tidak ada data sesuai pilihan.');
        Http::assertNothingSent();
    }

    public function test_dates_and_student_status_are_presented_without_mutating_values(): void
    {
        Http::preventStrayRequests();
        $d = MysqlFixtures::make();
        $d['semester']->update(['tanggal_mulai' => '2026-08-17', 'krs_mulai_pada' => '2026-08-01 08:15:00']);
        $d['profiles'][0]->update(['status' => 'aktif']);
        $this->actingAs($d['admin']);
        $this->get(route('dashboard.admin-akademik.semester.index'))->assertOk()->assertSee('academic-date')->assertSee('17/08/2026')->assertSee('01/08/2026 08:15')->assertSee('KRS dibuka (WIB)');
        $this->get(route('dashboard.admin-akademik.mahasiswa.index'))->assertOk()->assertSee('admin-state is-active', false)->assertSee('>Aktif</span>', false)->assertSee('Username akun')->assertSee('data-actions-panel', false)->assertSee('data-confirm-form inert', false)->assertDontSee('return confirm(');
        $this->assertSame('aktif', $d['profiles'][0]->fresh()->status);
        $this->assertSame('2026-08-01 08:15:00', $d['semester']->fresh()->krs_mulai_pada);
        Http::assertNothingSent();
    }
}
