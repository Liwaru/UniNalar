<?php

namespace Tests\Mysql;

use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

class AdminMasterTest extends MysqlTestCase
{
    use DatabaseTransactions;

    public function test_published_summaries_and_search_validation_on_mysql(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 90, 'nilai_huruf' => 'A']);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa'))->assertOk()
            ->assertViewHas('rataRataNilai', null)->assertViewHas('jumlahNilai', 0);
        app(GradeFinalizationService::class)->finalize($d['lecturerUser'], $grade->id);
        $this->get(route('dashboard.mahasiswa'))->assertOk()
            ->assertViewHas('rataRataNilai', 90.0)->assertViewHas('jumlahNilai', 1);
        $this->actingAs(User::factory()->create(['level' => 4]))
            ->get(route('dashboard.owner', ['program_studi_id' => $d['program']->id]))->assertOk()
            ->assertViewHas('totals', ['students' => 1, 'lecturers' => 1, 'programs' => 1]);
        $this->actingAs($d['admin']);
        foreach (['mahasiswa', 'kelas-kuliah'] as $resource) {
            $this->getJson(route('dashboard.admin-akademik.'.$resource.'.index', ['q' => ['invalid']]))
                ->assertUnprocessable()->assertJsonValidationErrors('q');
        }
    }

    public function test_login_rechecks_intended_role_with_mysql(): void
    {
        $user = User::factory()->create(['username' => 'qa_redirect_'.bin2hex(random_bytes(4)), 'level' => 3, 'password' => Hash::make('isolated-login-only')]);
        $this->get(route('dashboard.dosen'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'isolated-login-only'])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_used_credits_and_reserved_demo_email_are_protected_on_mysql(): void
    {
        $d = MysqlFixtures::make();
        $detail = MysqlFixtures::detail($d);
        $grade = app(GradeEntryService::class)->save($d['lecturerUser'], $detail->id, ['nilai_angka' => 90, 'nilai_huruf' => 'A']);
        app(GradeFinalizationService::class)->finalize($d['lecturerUser'], $grade->id);
        $url = route('dashboard.admin-akademik.mata-kuliah.update', $d['course']);
        $payload = $d['course']->only(['kode', 'nama', 'program_studi_id', 'sks']);
        $this->actingAs($d['admin'])->put($url, array_replace($payload, ['sks' => 4]))->assertSessionHasErrors('sks');
        $this->assertSame(3, $d['course']->fresh()->sks);
        $this->assertSame(4.0, app(AcademicGradeSummary::class)->forStudent($d['profiles'][0])['ipk']);
        $this->put($url, array_replace($payload, ['nama' => 'Algoritma dan Pemrograman Lanjut']))->assertSessionHasNoErrors();
        foreach (['mahasiswa', 'dosen'] as $resource) {
            $this->post(route('dashboard.admin-akademik.'.$resource.'.store'), [
                'nama' => 'Citra Ayu Lestari', 'nim' => '2026010099', 'angkatan' => 2026,
                'program_studi_id' => $d['program']->id, 'status' => 'aktif',
                'email' => $resource.'@DEMO.uninlar.test',
            ])->assertSessionHasErrors('email');
        }
        $this->actingAs($d['users'][0])->put(route('dashboard.mahasiswa.profil.update'), [
            'email' => 'alya@demo.uninlar.test',
        ])->assertSessionHasErrors('email');
        $this->assertNull($d['profiles'][0]->fresh()->email);
    }
}
