<?php

namespace Tests\Feature;

use App\Models\KelasKuliah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LecturerModuleScenarios;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class LecturerModuleTest extends TestCase
{
    use LecturerModuleScenarios, RefreshDatabase;

    public function test_empty_class_and_missing_grade_schema_have_controlled_states(): void
    {
        $d = MysqlFixtures::make();
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.show', $d['class']->id))->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['participants'] === 0);
        Schema::drop('nilai');
        $this->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Pengelolaan nilai belum tersedia');
        $this->get(route('dashboard.dosen.kelas.peserta', $d['class']->id))->assertOk()->assertSee('Status penilaian belum tersedia');
        $d['class']->delete();
        $this->get(route('dashboard.dosen'))->assertOk()->assertSee('Kelas operasional belum tersedia');
        $this->get(route('dashboard.dosen.kelas.index'))->assertOk()->assertSee('Kelas tidak ditemukan');
        Schema::drop('dosen');
        $this->get(route('dashboard.dosen.profil'))->assertOk()->assertSee('Schema akademik belum tersedia');
        $this->get(route('dashboard.dosen.kelas.index'))->assertOk()->assertSee('Schema akademik belum tersedia');
    }

    public function test_failed_grade_input_is_retained_only_for_its_own_form(): void
    {
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        MysqlFixtures::detail($d, 1);
        $page = route('dashboard.dosen.nilai', ['kelas_id' => $d['class']->id]);
        $this->actingAs($d['lecturerUser'])->from($page)->put(route('dashboard.dosen.nilai.save', $detail->id), [
            'nilai_angka' => 125, 'nilai_huruf' => 'B', 'editing_detail' => $detail->id, 'revision' => '',
        ])->assertSessionHasErrors('nilai_angka');
        $html = $this->get($page)->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'value="125"'));
        $this->assertStringContainsString('id="angka-'.$detail->id.'"', $html);
    }

    public function test_class_list_query_count_does_not_grow_per_class(): void
    {
        $d = MysqlFixtures::make();
        $this->actingAs($d['lecturerUser']);
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $this->get(route('dashboard.dosen.kelas.index'))->assertOk();
            $baseline = count(DB::getQueryLog());
            foreach (range(1, 15) as $index) {
                KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'PERF-'.$index]);
            }
            DB::flushQueryLog();
            $this->get(route('dashboard.dosen.kelas.index'))->assertOk()->assertViewHas('classes', fn ($p) => $p->total() === 16);
            $this->assertLessThanOrEqual($baseline + 1, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
