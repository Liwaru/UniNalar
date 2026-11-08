<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\FaktorRisiko;
use App\Models\RekomendasiIntervensi;
use App\Services\KrsSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MysqlFixtures;
use Tests\Support\StudentModuleScenarios;
use Tests\TestCase;

class StudentModuleTest extends TestCase
{
    use RefreshDatabase, StudentModuleScenarios;

    public function test_readable_submission_history_does_not_require_write_readiness(): void
    {
        $d = MysqlFixtures::make();
        $service = app(KrsSubmissionService::class);
        $service->addClass($d['users'][0], $d['class']->id);
        $service->submit($d['users'][0]);
        Schema::table('krs_pengajuan_riwayat', fn ($table) => $table->dropUnique(['krs_id', 'versi']));
        $this->assertFalse($service->workflowAvailable());
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.krs'))->assertOk()
            ->assertSee('Pengajuan KRS belum tersedia')->assertSee('Riwayat pengajuan')->assertSee('Versi 1')
            ->assertDontSee('Ajukan KRS');
    }

    public function test_missing_program_schema_does_not_crash_any_student_page(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        Schema::rename('program_studi', 'qa_hidden_programs');
        try {
            $this->actingAs($d['users'][0]);
            foreach (['', '.profil', '.krs', '.nilai', '.kehadiran', '.analisis-risiko', '.rekomendasi', '.tindak-lanjut'] as $suffix) {
                $this->get(route('dashboard.mahasiswa'.$suffix))->assertOk()->assertSee('Skema akademik belum lengkap');
            }
        } finally {
            Schema::rename('qa_hidden_programs', 'program_studi');
        }
    }

    public function test_recommendation_queries_do_not_grow_per_row(): void
    {
        $d = MysqlFixtures::make();
        $create = function (int $number) use ($d): void {
            $analysis = AnalisisRisiko::create(['mahasiswa_id' => $d['profiles'][0]->id, 'skor' => .2, 'kategori' => 'qa', 'dianalisis_pada' => now()]);
            $factor = FaktorRisiko::create(['analisis_risiko_id' => $analysis->id, 'kode_faktor' => 'QA-'.$number, 'nama' => 'Faktor QA']);
            RekomendasiIntervensi::create(['analisis_risiko_id' => $analysis->id, 'faktor_risiko_id' => $factor->id, 'judul' => 'Rekomendasi QA']);
        };
        $create(1);
        $create(2);
        $this->actingAs($d['users'][0]);
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $this->get(route('dashboard.mahasiswa.rekomendasi'))->assertOk();
            $small = count(DB::getQueryLog());
            foreach (range(3, 25) as $number) {
                $create($number);
            }
            DB::flushQueryLog();
            $this->get(route('dashboard.mahasiswa.rekomendasi'))->assertOk()
                ->assertViewHas('recommendations', fn ($p) => $p->count() === 25);
            $this->assertLessThanOrEqual($small + 1, count(DB::getQueryLog()), 'Recommendation rendering must eager-load factors and semesters.');
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_legacy_krs_remains_visible_when_workflow_schema_is_unavailable(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        Schema::drop('krs_pengajuan_riwayat');
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.krs'))->assertOk()
            ->assertSee('Pengajuan KRS belum tersedia')->assertSee($d['course']->nama)
            ->assertDontSee('Ajukan KRS')->assertViewHas('krs', fn ($records) => $records->count() === 1);
    }
}
