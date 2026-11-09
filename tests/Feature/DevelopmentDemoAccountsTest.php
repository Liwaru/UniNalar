<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\DevelopmentDemoAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class DevelopmentDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function academic(): array
    {
        $d = MysqlFixtures::make();
        $d['users'][0]->update(['name' => 'Hendrik', 'username' => 'hendrik', 'password' => 'legacy-student']);
        $d['profiles'][0]->update(['nama' => 'Hendrik', 'nim' => '24161033']);
        $d['lecturerUser']->update(['name' => 'Jovian', 'username' => 'jovian', 'password' => 'legacy-lecturer']);

        $d['lecturer']->update(['nama' => 'Jovian']);

        return ['student' => $d['profiles'][0]->id, 'lecturer' => $d['lecturer']->id];
    }

    public function test_four_demo_roles_are_hashed_idempotent_and_authorized(): void
    {
        $academic = $this->academic();
        $service = app(DevelopmentDemoAccounts::class);
        $ids = $service->create($academic);
        $hashes = User::whereIn('id', $ids)->pluck('password', 'id')->all();
        $count = User::count();
        $this->assertSame($ids, $service->create($academic, $ids));
        $this->assertSame($count, User::count());
        $this->assertSame($hashes, User::whereIn('id', $ids)->pluck('password', 'id')->all());
        $this->assertSame(DevelopmentDemoAccounts::SYNTHETIC_NIDN, Dosen::findOrFail($academic['lecturer'])->nidn);
        $this->assertSame(0, User::whereIn('username', ['hendrik', 'jovian', 'luiz', 'liwaru'])->count());
        $roles = ['mahasiswa' => 'mahasiswa', 'dosen' => 'dosen', 'admin' => 'admin-akademik', 'pimpinan' => 'owner'];
        foreach ($roles as $name => $role) {
            $user = User::findOrFail($ids[$name]);
            $this->assertTrue(Hash::check($name, $user->password));
            $this->assertNotSame($name, $user->password);
            $this->post(route('login.store'), ['username' => strtoupper($name), 'password' => $name])->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs($user);
            $this->get(route('dashboard'))->assertRedirect(route('dashboard.'.$role));
            $this->get(route('dashboard.'.$role))->assertOk();
            foreach ($roles as $otherRole) {
                if ($otherRole !== $role) {
                    $this->get(route('dashboard.'.$otherRole))->assertForbidden();
                }
            }
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_unowned_username_conflict_is_rejected_without_creating_other_account(): void
    {
        $academic = $this->academic();
        $foreign = User::factory()->create(['username' => 'admin', 'level' => 1]);
        $before = $foreign->getAttributes();
        try {
            app(DevelopmentDemoAccounts::class)->create($academic);
            $this->fail('Unowned account was adopted');
        } catch (RuntimeException) {
            $after = $foreign->fresh()->getAttributes();
            ksort($before);
            ksort($after);
            $this->assertSame($before, $after);
            $this->assertFalse(User::where('username', 'pimpinan')->exists());
        }
    }

    public function test_only_owned_demo_password_can_be_repaired_and_remember_token_is_cleared(): void
    {
        $academic = $this->academic();
        $service = app(DevelopmentDemoAccounts::class);
        $ids = $service->create($academic);
        $user = User::findOrFail($ids['admin']);
        $user->forceFill(['password' => 'changed-fixture-only', 'remember_token' => 'fixture-old-token'])->save();
        $service->create($academic, $ids);
        $this->assertTrue(Hash::check('admin', $user->fresh()->password));
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_legacy_registry_migrates_same_ids_and_preserves_academic_relations(): void
    {
        $academic = $this->academic();
        $student = Mahasiswa::findOrFail($academic['student']);
        $lecturer = Dosen::findOrFail($academic['lecturer']);
        $admin = User::factory()->create(['name' => 'Luiz', 'username' => 'luiz', 'level' => 3]);
        $owner = User::factory()->create(['name' => 'Liwaru', 'username' => 'liwaru', 'level' => 4]);
        $legacy = ['hendrik' => $student->user_id, 'jovian' => $lecturer->user_id, 'luiz' => $admin->id, 'liwaru' => $owner->id];
        $studentBefore = $student->getAttributes();
        $lecturerBefore = $lecturer->getAttributes();
        $classes = $lecturer->kelasKuliah()->pluck('id')->all();
        $ids = app(DevelopmentDemoAccounts::class)->create($academic, $legacy);
        $this->assertSame(array_values($legacy), array_values($ids));
        $this->assertSame($studentBefore, $student->fresh()->getAttributes());
        $this->assertSame($classes, $lecturer->fresh()->kelasKuliah()->pluck('id')->all());
        $this->assertSame(collect($lecturerBefore)->except(['nidn', 'updated_at'])->all(), collect($lecturer->fresh()->getAttributes())->except(['nidn', 'updated_at'])->all());
        foreach (array_keys($legacy) as $old) {
            $this->post(route('login.store'), ['username' => $old, 'password' => 'invalid-legacy'])->assertSessionHasErrors('username');
        }
        $this->assertSame($ids, app(DevelopmentDemoAccounts::class)->create($academic, $ids));
    }

    public function test_nidn_conflict_rolls_back_credentials_without_partial_changes(): void
    {
        $academic = $this->academic();
        Dosen::create(['nama' => 'Unrelated QA', 'nidn' => DevelopmentDemoAccounts::SYNTHETIC_NIDN, 'aktif' => true]);
        $before = User::orderBy('id')->get()->toArray();
        try {
            app(DevelopmentDemoAccounts::class)->create($academic);
            $this->fail('NIDN collision accepted');
        } catch (RuntimeException) {
            $this->assertSame($before, User::orderBy('id')->get()->toArray());
        }
    }

    public function test_registry_cannot_redirect_an_academic_account_to_another_user(): void
    {
        $academic = $this->academic();
        $other = User::factory()->create(['level' => 1]);
        $before = User::orderBy('id')->get()->toArray();
        try {
            app(DevelopmentDemoAccounts::class)->create($academic, ['mahasiswa' => $other->id]);
            $this->fail('Registry identity mismatch accepted');
        } catch (RuntimeException) {
            $this->assertSame($before, User::orderBy('id')->get()->toArray());
        }
    }

    public function test_testing_cannot_provision_a_file_database(): void
    {
        $academic = $this->academic();
        config(['database.connections.sqlite.database' => '/tmp/not-an-owned-database.sqlite']);
        $this->expectException(RuntimeException::class);
        app(DevelopmentDemoAccounts::class)->create($academic);
    }

    public function test_production_environment_is_rejected_before_writes(): void
    {
        $academic = $this->academic();
        $this->app['env'] = 'production';
        $this->expectException(RuntimeException::class);
        app(DevelopmentDemoAccounts::class)->create($academic);
    }
}
