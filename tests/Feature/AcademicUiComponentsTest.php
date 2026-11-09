<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Services\KrsSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class AcademicUiComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_views_share_complete_details_forms_and_fail_closed_confirmation(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $d = MysqlFixtures::make();
        $this->actingAs($d['admin']);
        $records = ['program-studi' => $d['program'], 'semester' => $d['semester'], 'mata-kuliah' => $d['course'], 'dosen' => $d['lecturer'], 'mahasiswa' => $d['profiles'][0], 'kelas-kuliah' => $d['class']];
        foreach ($records as $resource => $record) {
            $route = 'dashboard.admin-akademik.'.$resource.'.';
            $index = $this->get(route($route.'index'))->assertOk()->assertSee('data-confirm-form inert', false)->assertSee('type="button" disabled data-confirm-trigger', false)->assertDontSee('return confirm(');
            $this->assertSame(1, substr_count($index->getContent(), 'id="uninalar-confirmation"'));
            $this->get(route($route.'show', $record))->assertOk()->assertSee('detail-fields')->assertSee('Kembali ke daftar');
            foreach (['create', 'edit'] as $action) {
                $this->get(route($route.$action, $action === 'edit' ? $record : []))->assertOk()->assertSee('admin-form-grid')->assertSee('name="_token"', false)->assertSee('Batal');
            }
        }
        $this->get(route('dashboard.admin-akademik.semester.create'))->assertSee('Identitas semester')->assertSee('Jadwal akademik')->assertSee('Periode KRS (WIB)');
        $d['program']->update(['nama' => '<img src=x onerror=alert(1)>']);
        $this->get(route('dashboard.admin-akademik.program-studi.index'))->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x', false);
        Http::assertNothingSent();
    }

    public function test_student_lecturer_and_admin_confirmation_contracts_preserve_role_actions(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        $grade = Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 78, 'nilai_huruf' => 'B', 'nilai_mutu' => 3]);
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.nilai'))->assertOk()->assertSee('Finalisasi nilai?')->assertSee(route('dashboard.dosen.nilai.finalize', $grade))->assertSee('data-confirm-form inert', false);
        $student = $d['users'][1];
        app(KrsSubmissionService::class)->addClass($student, $d['class']->id);
        $this->actingAs($student)->get(route('dashboard.mahasiswa.krs'))->assertOk()->assertSee('Hapus pilihan kelas?')->assertSee('Ajukan KRS?');
        app(KrsSubmissionService::class)->submit($student);
        $this->actingAs($d['admin'])->get(route('dashboard.admin-akademik.krs-pengajuan.index'))->assertOk()->assertSee('Setujui pengajuan KRS?')->assertSee('Tolak pengajuan KRS?');
        $this->actingAs($student)->get(route('dashboard.admin-akademik.program-studi.index'))->assertForbidden();
        Http::assertNothingSent();
    }
}
