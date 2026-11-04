<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_the_existing_username_flow_and_help_dialog(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('login-', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('data-open-help', false)
            ->assertSee('Silakan hubungi dosen pembimbing', false)
            ->assertDontSee('name="level"', false)
            ->assertDontSee('Daftar');
    }

    public function test_failed_login_shows_the_backend_authentication_error(): void
    {
        $this->post(route('login.store'), [
            'username' => 'tidak-ada',
            'password' => 'salah',
        ])->assertRedirect()->assertSessionHasErrors('username');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Username atau password tidak valid.');
    }

    public function test_remember_checkbox_uses_laravel_persistent_authentication(): void
    {
        $user = User::create([
            'name' => 'Demo Akademik',
            'username' => 'demo-akademik',
            'email' => 'demo-akademik@example.test',
            'level' => User::ROLE_ADMIN_AKADEMIK,
            'password' => 'password-demo',
        ]);

        $this->post(route('login.store'), [
            'username' => 'demo-akademik',
            'password' => 'password-demo',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_successful_login_and_logout_rotate_session_identifiers(): void
    {
        $user = User::factory()->create([
            'username' => 'session-check',
            'level' => User::ROLE_MAHASISWA,
            'password' => 'session-password',
        ]);

        $this->withSession(['pre_login_state' => 'present']);
        $beforeLogin = session()->getId();

        $this->post(route('login.store'), [
            'username' => 'session-check',
            'password' => 'session-password',
        ])->assertRedirect(route('dashboard'));

        $afterLogin = session()->getId();
        $this->assertNotSame($beforeLogin, $afterLogin);
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($afterLogin, session()->getId());
    }

    public function test_login_route_throttles_repeated_failed_attempts(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login.store'), [
                'username' => 'unknown-user',
                'password' => 'incorrect',
            ])->assertRedirect()->assertSessionHasErrors('username');
        }

        $this->post(route('login.store'), [
            'username' => 'unknown-user',
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }
}
