<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\DevelopmentAcademicFixtures;
use App\Services\DevelopmentDemoAccounts;
use App\Services\GradeEntryService;
use App\Services\UsernameNormalization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class UsernameAcademicFixturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_is_canonical_unique_and_never_falls_back_to_nim_or_email(): void
    {
        $d = MysqlFixtures::make();
        $user = $d['users'][0];
        $user->update(['username' => ' Hendrik ', 'password' => 'development-test-only']);
        $this->assertSame('hendrik', $user->fresh()->username);
        $this->assertTrue(Hash::check('development-test-only', $user->fresh()->password));
        $this->assertNotSame('development-test-only', $user->password);
        $this->post(route('login.store'), ['username' => $d['profiles'][0]->nim, 'password' => 'development-test-only'])->assertSessionHasErrors('username');
        $this->post(route('login.store'), ['username' => $user->email, 'password' => 'development-test-only'])->assertSessionHasErrors('username');
        $this->post(route('login.store'), ['username' => ' HENDRIK ', 'password' => 'development-test-only'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->expectException(QueryException::class);
        User::factory()->create(['username' => 'HENDRIK']);
    }

    public function test_existing_username_normalization_preflights_collisions_before_any_change(): void
    {
        $a = User::factory()->create(['username' => 'one']);
        $b = User::factory()->create(['username' => 'two']);
        DB::table('users')->where('id', $a->id)->update(['username' => ' Legacy ']);
        DB::table('users')->where('id', $b->id)->update(['username' => 'legacy']);
        try {
            UsernameNormalization::apply();
            $this->fail('Collision was not rejected');
        } catch (RuntimeException) {
            $this->assertSame(' Legacy ', $a->fresh()->username);
            $this->assertSame('legacy', $b->fresh()->username);
        }
        DB::table('users')->where('id', $b->id)->update(['username' => null]);
        UsernameNormalization::apply();
        $this->assertSame('legacy', $a->fresh()->username);
        $this->assertNull($b->fresh()->username);
    }

    public function test_realistic_fixture_is_idempotent_finalized_through_services_and_scoped(): void
    {
        $d = MysqlFixtures::make();
        $before = Semester::where('aktif', true)->pluck('id')->all();
        $clock = now()->format('Y-m-d');
        $semesterUpdated = $d['semester']->fresh()->updated_at->toDateTimeString();
        $fixture = app(DevelopmentAcademicFixtures::class)->create($d['admin']);
        $this->assertSame($fixture, app(DevelopmentAcademicFixtures::class)->create($d['admin'], $fixture));
        $this->assertSame($before, Semester::where('aktif', true)->pluck('id')->all());
        $this->assertSame($clock, now()->format('Y-m-d'));
        $this->assertSame($semesterUpdated, $d['semester']->fresh()->updated_at->toDateTimeString());
        $hendrik = User::where('username', 'mahasiswa')->firstOrFail();
        $jovian = User::where('username', 'dosen')->firstOrFail();
        $this->assertTrue(Hash::check('mahasiswa', $hendrik->password));
        $this->assertTrue(Hash::check('dosen', $jovian->password));
        $this->assertSame('24161033', $hendrik->mahasiswa->nim);
        $this->assertSame(DevelopmentDemoAccounts::SYNTHETIC_NIDN, $jovian->dosen->nidn);
        $this->assertSame(15, $jovian->dosen->kelasKuliah()->count());
        $this->assertSame(3, $hendrik->mahasiswa->krs()->count());
        $this->assertSame(14, Nilai::whereIn('id', $fixture['grades'])->whereNotNull('final_at')->count());
        $this->assertSame(1, Nilai::whereIn('id', $fixture['grades'])->whereNull('final_at')->count());
        $summary = app(AcademicGradeSummary::class)->forStudent($hendrik->mahasiswa);
        $this->assertSame(3.63, $summary['ips']);
        $this->assertSame(3.26, $summary['ipk']);
        $this->assertSame(14, $summary['final_count']);
        $program = $hendrik->mahasiswa->programStudi;
        $program->update(['nama' => 'Teknik Informatika (fixture sintetis)']);
        $gradesBefore = Nilai::orderBy('id')->get()->toArray();
        $historyBefore = app(AcademicGradeSummary::class)->historyForStudent($hendrik->mahasiswa)->all();
        $this->assertSame($fixture, app(DevelopmentAcademicFixtures::class)->create($d['admin'], $fixture));
        $this->assertSame('Teknik Informatika', $program->fresh()->nama);
        $this->assertSame($gradesBefore, Nilai::orderBy('id')->get()->toArray());
        $this->assertSame($historyBefore, app(AcademicGradeSummary::class)->historyForStudent($hendrik->mahasiswa)->all());
        $this->assertSame($summary, app(AcademicGradeSummary::class)->forStudent($hendrik->mahasiswa));

        $this->assertSame(120, DB::table('kehadiran')->count());
        $this->assertSame(0, count(DB::select('PRAGMA foreign_key_check')));
        $this->assertSame(3, DB::table('krs_pengajuan_riwayat')->where('status', 'disetujui')->count());
        foreach (['mahasiswa', 'dosen'] as $role) {
            $user = $role === 'mahasiswa' ? $hendrik : $jovian;
            $this->post(route('login.store'), ['username' => strtoupper($user->username), 'password' => $user->username])->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs($user);
            $this->get(route('dashboard.'.$role))->assertOk();
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
        $this->actingAs($d['lecturerUser'])->get(route('dashboard.dosen.kelas.show', $fixture['classes'][0]))->assertNotFound();
        $this->actingAs($jovian)->get(route('dashboard.dosen.kelas.show', $d['class']->id))->assertNotFound();
        $final = Nilai::whereIn('id', $fixture['grades'])->whereNotNull('final_at')->first();
        $this->actingAs($jovian)->put(route('dashboard.dosen.nilai.save', $final->krs_detail_id), [
            'nilai_angka' => 99, 'nilai_huruf' => 'A', 'revision' => GradeEntryService::revision($final),
        ])->assertSessionHasErrors('nilai');
    }

    public function test_nim_or_username_conflict_rolls_back_without_overwriting_existing_profile(): void
    {
        $d = MysqlFixtures::make();
        $d['profiles'][0]->update(['nim' => '24161033']);
        $users = User::count();
        try {
            app(DevelopmentAcademicFixtures::class)->create($d['admin']);
            $this->fail('NIM collision accepted');
        } catch (RuntimeException) {
            $this->assertSame($users, User::count());
            $this->assertSame('24161033', $d['profiles'][0]->fresh()->nim);
            $this->assertNull(User::where('username', 'dosen')->first());
        }
    }
}
