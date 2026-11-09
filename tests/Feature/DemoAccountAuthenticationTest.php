<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\AcademicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_login_with_username_and_are_authorized_by_level(): void
    {
        $this->seed(AcademicDemoSeeder::class);

        $accounts = [
            ['username' => 'mahasiswa', 'password' => 'mahasiswa', 'level' => User::ROLE_MAHASISWA, 'dashboard' => 'dashboard.mahasiswa'],
            ['username' => 'dosen', 'password' => 'dosen', 'level' => User::ROLE_DOSEN, 'dashboard' => 'dashboard.dosen'],
            ['username' => 'admin', 'password' => 'admin', 'level' => User::ROLE_ADMIN_AKADEMIK, 'dashboard' => 'dashboard.admin-akademik'],
            ['username' => 'pimpinan', 'password' => 'pimpinan', 'level' => User::ROLE_KAPRODI, 'dashboard' => 'dashboard.owner'],
        ];

        foreach ($accounts as $account) {
            $user = User::where('username', $account['username'])->firstOrFail();

            $this->assertSame($account['level'], $user->level);
            $this->assertTrue(Hash::check($account['password'], $user->password));
            $this->post(route('login.store'), [
                'username' => $account['username'],
                'password' => $account['password'],
            ])->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            $this->get(route('dashboard'))->assertRedirect(route($account['dashboard']));
            $dashboard = $this->get(route($account['dashboard']))
                ->assertOk()
                ->assertSee('href="'.route('dashboard').'"', false);

            if ($account['level'] === User::ROLE_MAHASISWA) {
                $dashboard->assertSee('KRS &amp; Mata Kuliah', false)
                    ->assertSee('>Mahasiswa</strong>', false)
                    ->assertSee('student-avatar" aria-hidden="true">M</div>', false)
                    ->assertDontSee('Akun demo')
                    ->assertSee('Belum tersedia')
                    ->assertDontSee('0.4200');
            } elseif ($account['level'] === User::ROLE_ADMIN_AKADEMIK) {
                $dashboard->assertSee('Akun demo.')
                    ->assertSee('Data akademik')
                    ->assertViewHas('totals', fn ($totals) => $totals->count() === 6 && $totals->sum() === 0)
                    ->assertDontSee('0.4200');
            } else {
                $dashboard->assertSee('Akun demo.')
                    ->assertSee($account['level'] === User::ROLE_DOSEN ? 'Profil dosen belum tertaut' : 'Data operasional belum tersedia')
                    ->assertDontSee('0.4200');
            }

            $this->post(route('logout'))->assertRedirect(route('login'));
        }

        $studentUser = User::where('username', 'mahasiswa')->firstOrFail();
        $lecturerUser = User::where('username', 'dosen')->firstOrFail();
        $this->assertSame('DEMO00001', Mahasiswa::where('user_id', $studentUser->id)->value('nim'));
        $this->assertNotNull(Dosen::where('user_id', $lecturerUser->id)->first());

        $this->seed(AcademicDemoSeeder::class);
        $this->assertSame(40, Mahasiswa::where('nim', 'like', 'DEMO%')->count());
        $this->assertSame(4, User::whereIn('username', ['mahasiswa', 'dosen', 'admin', 'pimpinan'])->count());

        $this->actingAs($studentUser)->get(route('area.admin-akademik'))->assertForbidden();
        $this->actingAs(User::where('username', 'admin')->firstOrFail())
            ->get(route('area.admin-akademik'))->assertOk();
        $this->actingAs(User::where('username', 'pimpinan')->firstOrFail())
            ->get(route('area.kaprodi'))->assertOk();
    }

    public function test_synthetic_seeder_preserves_hashes_and_rejects_unrelated_existing_accounts(): void
    {
        $foreign = User::factory()->create(['username' => 'dosen', 'email' => 'unrelated@qa.example.test', 'level' => 2]);
        $before = $foreign->getAttributes();
        try {
            $this->seed(AcademicDemoSeeder::class);
            $this->fail('Seeder adopted an unrelated account');
        } catch (\LogicException) {
            $after = $foreign->fresh()->getAttributes();
            ksort($before);
            ksort($after);
            $this->assertTrue($before === $after, 'Unrelated account attributes must remain identical.');
            $this->assertSame(1, User::count());
        }
        $foreign->delete();
        $this->seed(AcademicDemoSeeder::class);
        $ids = User::orderBy('id')->pluck('id')->all();
        $hashes = User::orderBy('id')->pluck('password')->all();
        $this->seed(AcademicDemoSeeder::class);
        $this->assertSame($ids, User::orderBy('id')->pluck('id')->all());
        $this->assertTrue($hashes === User::orderBy('id')->pluck('password')->all(), 'Repeated seed must preserve password hashes.');
        $this->assertFalse(User::where('username', 'owner')->exists());
    }

    public function test_role_area_routes_enforce_backend_levels_for_every_role(): void
    {
        $routes = [
            'area.mahasiswa' => [User::ROLE_MAHASISWA, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI],
            'area.dosen' => [User::ROLE_DOSEN, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI],
            'area.admin-akademik' => [User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI],
            'area.kaprodi' => [User::ROLE_KAPRODI],
        ];

        foreach ([User::ROLE_MAHASISWA, User::ROLE_DOSEN, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI] as $level) {
            $user = User::factory()->create(['level' => $level]);

            foreach ($routes as $route => $allowedLevels) {
                $response = $this->actingAs($user)->get(route($route));

                if (in_array($level, $allowedLevels, true)) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    public function test_each_role_dashboard_requires_its_own_authorized_level(): void
    {
        $routes = [
            'dashboard.mahasiswa' => User::ROLE_MAHASISWA,
            'dashboard.dosen' => User::ROLE_DOSEN,
            'dashboard.admin-akademik' => User::ROLE_ADMIN_AKADEMIK,
            'dashboard.owner' => User::ROLE_KAPRODI,
        ];

        foreach ([User::ROLE_MAHASISWA, User::ROLE_DOSEN, User::ROLE_ADMIN_AKADEMIK, User::ROLE_KAPRODI] as $level) {
            $user = User::factory()->create(['level' => $level]);

            foreach ($routes as $route => $requiredLevel) {
                $response = $this->actingAs($user)->get(route($route));
                $level === $requiredLevel ? $response->assertOk() : $response->assertForbidden();
            }
        }
    }

    public function test_all_role_dashboards_render_one_shared_sidebar_with_only_their_authorized_menu(): void
    {
        $menus = [
            User::ROLE_MAHASISWA => [
                'role' => 'Mahasiswa',
                'dashboard' => 'dashboard.mahasiswa',
                'allowed' => ['dashboard.mahasiswa.krs', 'dashboard.mahasiswa.nilai', 'dashboard.mahasiswa.kehadiran', 'dashboard.mahasiswa.analisis-risiko', 'dashboard.mahasiswa.rekomendasi', 'dashboard.mahasiswa.tindak-lanjut', 'dashboard.mahasiswa.profil'],
                'forbidden' => ['dashboard.dosen', 'dashboard.admin-akademik', 'dashboard.owner'],
            ],
            User::ROLE_DOSEN => [
                'role' => 'Dosen',
                'dashboard' => 'dashboard.dosen',
                'allowed' => ['dashboard.dosen.nilai'],
                'forbidden' => ['dashboard.mahasiswa.krs', 'dashboard.admin-akademik', 'dashboard.owner'],
            ],
            User::ROLE_ADMIN_AKADEMIK => [
                'role' => 'Admin Akademik',
                'dashboard' => 'dashboard.admin-akademik',
                'allowed' => ['dashboard.admin-akademik.program-studi.index', 'dashboard.admin-akademik.semester.index', 'dashboard.admin-akademik.mata-kuliah.index', 'dashboard.admin-akademik.dosen.index', 'dashboard.admin-akademik.mahasiswa.index', 'dashboard.admin-akademik.kelas-kuliah.index', 'dashboard.admin-akademik.krs-pengajuan.index'],
                'forbidden' => ['dashboard.mahasiswa.krs', 'dashboard.dosen', 'dashboard.owner'],
            ],
            User::ROLE_KAPRODI => [
                'role' => 'Pimpinan Yayasan',
                'dashboard' => 'dashboard.owner',
                'allowed' => [],
                'forbidden' => ['dashboard.mahasiswa.krs', 'dashboard.dosen', 'dashboard.admin-akademik'],
            ],
        ];

        foreach ($menus as $level => $menu) {
            $user = User::factory()->create(['level' => $level]);
            $response = $this->actingAs($user)->get(route($menu['dashboard']))->assertOk();
            $content = $response->getContent();
            preg_match('/<aside class="student-sidebar".*?<\/aside>/s', $content, $sidebarMatches);

            $this->assertCount(1, $sidebarMatches, "Expected exactly one shared sidebar for {$menu['role']}.");
            $sidebar = $sidebarMatches[0];
            $this->assertStringContainsString('aria-label="Navigasi '.$menu['role'].'"', $sidebar);
            $this->assertStringContainsString('href="'.route('dashboard').'"', $sidebar);

            foreach ($menu['allowed'] as $routeName) {
                $this->assertStringContainsString('href="'.route($routeName).'"', $sidebar, "Missing {$routeName} for {$menu['role']}.");
            }

            foreach ($menu['forbidden'] as $routeName) {
                $this->assertStringNotContainsString('href="'.route($routeName).'"', $sidebar, "Unexpected {$routeName} for {$menu['role']}.");
            }
        }
    }

    public function test_sidebar_uses_authenticated_user_level_instead_of_a_mutable_level_session_value(): void
    {
        $student = User::factory()->create(['level' => User::ROLE_MAHASISWA]);
        $this->actingAs($student)->withSession(['level' => User::ROLE_ADMIN_AKADEMIK])
            ->get(route('dashboard.mahasiswa'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard.mahasiswa.krs').'"', false)
            ->assertDontSee('href="'.route('dashboard.admin-akademik.program-studi.index').'"', false);

        $admin = User::factory()->create(['level' => User::ROLE_ADMIN_AKADEMIK]);
        $this->actingAs($admin)->withSession(['level' => User::ROLE_MAHASISWA])
            ->get(route('dashboard.admin-akademik'))
            ->assertOk()
            ->assertSee('href="'.route('dashboard.admin-akademik.program-studi.index').'"', false)
            ->assertDontSee('href="'.route('dashboard.mahasiswa.krs').'"', false);
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create(['level' => User::ROLE_DOSEN]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
