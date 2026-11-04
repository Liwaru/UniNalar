<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginIntendedRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_does_not_send_admin_to_an_intended_lecturer_page(): void
    {
        $user = User::factory()->create(['username' => 'qa_intended_admin', 'level' => 3, 'password' => Hash::make('isolated-login-only')]);
        $this->get(route('dashboard.dosen'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'isolated-login-only'])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertRedirect(route('dashboard.admin-akademik'));
    }

    public function test_authorized_intended_url_is_preserved(): void
    {
        $user = User::factory()->create(['username' => 'qa_intended_student', 'level' => 1, 'password' => Hash::make('isolated-login-only')]);
        $this->get(route('dashboard.mahasiswa.nilai'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'isolated-login-only'])->assertSessionHasNoErrors()->assertRedirect(route('dashboard.mahasiswa.nilai'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_external_or_missing_intended_route_falls_back_safely(): void
    {
        foreach (['https://outside.example.test/dashboard', '/route-tidak-ada'] as $intended) {
            $user = User::factory()->create(['username' => 'qa_admin_'.User::count(), 'level' => 3, 'password' => Hash::make('isolated-login-only')]);
            $this->withSession(['url.intended' => $intended])->post(route('login.store'), [
                'username' => $user->username, 'password' => 'isolated-login-only',
            ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
            $this->post(route('logout'));
        }
    }
}
