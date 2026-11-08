<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MysqlFixtures;
use Tests\Support\OwnerModuleScenarios;
use Tests\TestCase;

class OwnerModuleTest extends TestCase
{
    use OwnerModuleScenarios, RefreshDatabase;

    public function test_empty_and_missing_schema_owner_pages_are_controlled(): void
    {
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner'))->assertOk()->assertViewHas('totals', ['students' => 0, 'lecturers' => 0, 'programs' => 0]);
        foreach ($this->ownerPages() as $page) {
            $this->get(route('dashboard.owner.'.$page.'.index'))->assertOk()->assertViewHas('records', fn ($p) => $p->total() === 0);
        }
        Schema::rename('program_studi', 'qa_hidden_programs');
        try {
            $this->get(route('dashboard.owner'))->assertOk()->assertViewHas('available', false);
            foreach ($this->ownerPages() as $page) {
                $this->get(route('dashboard.owner.'.$page.'.index'))->assertOk()->assertViewHas('available', false);
            }
            $this->get(route('dashboard.owner.mahasiswa.show', 1))->assertStatus(503);
        } finally {
            Schema::rename('qa_hidden_programs', 'program_studi');
        }
    }

    public function test_legacy_schema_does_not_publish_draft_or_guess_final_status(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 99, 'nilai_huruf' => 'A', 'nilai_mutu' => 4]);
        Schema::table('nilai', fn ($table) => $table->dropIndex(['final_at']));
        Schema::table('nilai', fn ($table) => $table->dropColumn('final_at'));
        $this->actingAs(User::factory()->create(['level' => 4]))->get(route('dashboard.owner.nilai.index'))->assertOk()->assertViewHas('available', false)->assertDontSee('99.00');
        $this->get(route('dashboard.owner.kelas.show', $d['class']->id))->assertOk()->assertDontSee('99.00')->assertViewHas('finalizationAvailable', false);
        $this->get(route('dashboard.owner'))->assertOk()->assertViewHas('stats', fn ($s) => $s['ips'] === null && $s['ipk'] === null)->assertViewHas('metrics', fn ($m) => $m['final'] === null);
    }
}
