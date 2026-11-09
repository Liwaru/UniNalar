<?php

namespace Tests\Mysql;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\MysqlTestCase;

class HostingAuthenticationTest extends MysqlTestCase
{
    use DatabaseTransactions;

    public function test_all_four_roles_login_logout_and_reject_cross_role_dashboards(): void
    {
        Http::preventStrayRequests();
        $destinations = [1 => 'dashboard.mahasiswa', 2 => 'dashboard.dosen', 3 => 'dashboard.admin-akademik', 4 => 'dashboard.owner'];
        foreach ($destinations as $role => $destination) {
            $user = User::factory()->create(['level' => $role, 'password' => 'synthetic-login-test-only']);
            $this->get(route('login'))->assertOk();
            $this->post(route('login.store'), ['username' => $user->username, 'password' => 'synthetic-login-test-only'])
                ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
            $this->get(route('dashboard'))->assertRedirect(route($destination));
            $this->get(route($destination))->assertOk();
            foreach ($destinations as $otherRole => $otherDestination) {
                if ($otherRole !== $role) {
                    $this->get(route($otherDestination))->assertForbidden();
                }
            }
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
            $this->get(route($destination))->assertRedirect(route('login'));
        }
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with((string) $route->getName(), 'dashboard.owner')) {
                $this->assertSame([], array_values(array_diff($route->methods(), ['GET', 'HEAD'])));
            }
        }
        Http::assertNothingSent();
    }
}
