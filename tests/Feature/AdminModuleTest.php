<?php

namespace Tests\Feature;

use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AdminModuleScenarios;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use AdminModuleScenarios, RefreshDatabase;

    public function test_empty_dashboard_is_zero_and_missing_schema_is_controlled(): void
    {
        $this->actingAs(User::factory()->create(['level' => 3]))->get(route('dashboard.admin-akademik'))->assertOk()
            ->assertViewHas('totals', fn ($s) => $s->count() === 6 && $s->sum() === 0);
        Schema::rename('program_studi', 'qa_hidden_programs');
        try {
            $this->get(route('dashboard.admin-akademik'))->assertOk()->assertSee('Data akademik belum tersedia');
            $this->get(route('dashboard.admin-akademik.program-studi.index'))->assertStatus(503);
        } finally {
            Schema::rename('qa_hidden_programs', 'program_studi');
        }
    }

    public function test_unique_constraint_after_validation_is_reported_without_sql_leakage(): void
    {
        $this->actingAs(User::factory()->create(['level' => 3]));
        $inject = true;
        DB::connection()->beforeExecuting(function ($sql) use (&$inject): void {
            if ($inject && str_contains(strtolower($sql), 'insert into "program_studi"')) {
                $inject = false;
                ProgramStudi::create(['kode' => 'QA-RACE', 'nama' => 'Saingan sintetis QA', 'jenjang' => 'S1']);
            }
        });
        $response = $this->post(route('dashboard.admin-akademik.program-studi.store'), ['kode' => 'QA-RACE', 'nama' => 'Program QA', 'jenjang' => 'S1']);
        $response->assertSessionHasErrors('data');
        $this->assertStringNotContainsString('SQLSTATE', session('errors')->first('data'));
        $this->assertDatabaseMissing('program_studi', ['kode' => 'QA-RACE']);
    }

    public function test_admin_class_roster_pagination_eager_loads_students_and_programs(): void
    {
        $d = MysqlFixtures::make(22);
        foreach (range(0, 21) as $index) {
            MysqlFixtures::detail($d, $index);
        }
        $this->actingAs($d['admin'])->get(route('dashboard.admin-akademik.kelas-kuliah.show', $d['class']->id))->assertOk()
            ->assertViewHas('participants', fn ($p) => $p->total() === 22 && $p->count() === 20);
        $this->get(route('dashboard.admin-akademik.kelas-kuliah.show', ['kelas_kuliah' => $d['class']->id, 'page' => 2]))->assertOk()
            ->assertViewHas('participants', fn ($p) => $p->count() === 2);
    }
}
