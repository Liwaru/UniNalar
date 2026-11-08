<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Nilai;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class PublishedAcademicSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_grades_do_not_enter_published_average_or_trend(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 91, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);
        $semester = Semester::create(['kode' => 'QA-PAST', 'tahun_ajaran' => '2025/2026', 'periode' => 2]);
        $class = KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $semester->id, 'kode_kelas' => 'PAST']);
        $krs = Krs::create(['mahasiswa_id' => $d['profiles'][0]->id, 'semester_id' => $semester->id, 'status' => 'disetujui']);
        $past = KrsDetail::create(['krs_id' => $krs->id, 'kelas_kuliah_id' => $class->id]);
        Nilai::create(['krs_detail_id' => $past->id, 'nilai_angka' => 80, 'nilai_huruf' => 'B', 'nilai_mutu' => 3, 'final_at' => now()]);

        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))
            ->assertOk()->assertViewHas('rataRataNilai', 80.0)->assertViewHas('jumlahNilai', 1)
            ->assertViewHas('grafik', fn ($g) => $g['nilai'] === [80.0, null] && ! $g['tersedia']);
        $this->get(route('dashboard.mahasiswa.nilai'))->assertOk()->assertSee('91.00')->assertSee('Belum final');
        $grade->update(['final_at' => now()]);
        $this->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('rataRataNilai', 85.5)
            ->assertViewHas('jumlahNilai', 2)->assertViewHas('grafik', fn ($g) => $g['tersedia']);
        $detail->krs->update(['status' => 'ditolak']);
        $this->get(route('dashboard.mahasiswa'))->assertOk()->assertViewHas('rataRataNilai', 80.0);
    }

    public function test_legacy_grades_without_finalization_schema_do_not_become_published_values(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 91, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);
        Schema::table('nilai', fn ($table) => $table->dropIndex(['final_at']));
        Schema::table('nilai', fn ($table) => $table->dropColumn('final_at'));
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()
            ->assertViewHas('rataRataNilai', null)->assertViewHas('jumlahNilai', 0);
    }

    public function test_owner_totals_include_empty_programs_and_exclude_demo_profiles(): void
    {
        $d = MysqlFixtures::make();
        $empty = ProgramStudi::create(['kode' => 'QA-EMPTY', 'nama' => 'Program QA Kosong', 'jenjang' => 'S1']);
        $demo = ProgramStudi::create(['kode' => 'QA-DEMO', 'nama' => 'Program Demo', 'jenjang' => 'S1']);
        Dosen::create(['nama' => 'Dosen Demo QA', 'program_studi_id' => $demo->id]);
        Dosen::create(['nama' => 'Dosen Tanpa Program QA']);
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner'))
            ->assertOk()->assertViewHas('totals', ['students' => 1, 'lecturers' => 2, 'programs' => 2]);
        $this->get(route('dashboard.owner', ['program_studi_id' => $empty->id]))->assertOk()
            ->assertViewHas('totals', ['students' => 0, 'lecturers' => 0, 'programs' => 1])->assertSee('Data operasional belum tersedia');
        $this->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id, 'semester_id' => $d['semester']->id]))
            ->assertOk()->assertViewHas('totals', ['students' => 1, 'lecturers' => 1, 'programs' => 1]);
        $this->getJson(route('dashboard.owner', ['program_studi_id' => [$empty->id]]))->assertUnprocessable();
    }
}
