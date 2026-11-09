<?php

namespace Tests\Feature;

use App\Models\Nilai;
use App\Models\User;
use App\Services\HostingDemoProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\HostingProvisioningScenarios;
use Tests\TestCase;

class HostingProvisioningTest extends TestCase
{
    use HostingProvisioningScenarios, RefreshDatabase;

    public function test_cli_writes_unique_passwords_privately_without_printing_or_overwriting_them(): void
    {
        $database = $this->configureProvisioning();
        $directory = sys_get_temp_dir().'/uninalar-credentials-'.Str::random(12);
        mkdir($directory, 0700);
        $file = $directory.'/accounts.json';
        try {
            $this->assertSame(0, Artisan::call('uninalar:demo-prepare', ['--confirm-database' => $database, '--credentials-file' => $file]));
            $output = Artisan::output();
            $accounts = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(0600, fileperms($file) & 0777);
            $this->assertCount(4, $accounts);
            $this->assertCount(4, array_unique(array_column($accounts, 'password')));
            foreach ($accounts as $account) {
                $this->assertGreaterThanOrEqual(24, strlen($account['password']));
                $this->assertTrue(Hash::check($account['password'], User::where('username', $account['username'])->sole()->password));
                $this->assertStringNotContainsString($account['password'], $output);
            }
            $before = file_get_contents($file);
            $this->assertSame(1, Artisan::call('uninalar:demo-prepare', ['--confirm-database' => $database, '--credentials-file' => $file]));
            $this->assertSame($before, file_get_contents($file));
            $this->assertSame(4, User::count());
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public function test_cli_rejects_credentials_inside_application_root_before_any_write(): void
    {
        $database = $this->configureProvisioning();
        $file = base_path('bootstrap/cache/qa-forbidden-credentials.json');
        $this->assertSame(1, Artisan::call('uninalar:demo-prepare', ['--confirm-database' => $database, '--credentials-file' => $file]));
        $this->assertFileDoesNotExist($file);
        $this->assertSame(0, User::count());
    }

    public function test_cli_database_failure_is_atomic_and_retains_private_credentials_for_recovery(): void
    {
        $database = $this->configureProvisioning();
        $directory = sys_get_temp_dir().'/uninalar-credentials-'.Str::random(12);
        mkdir($directory, 0700);
        $file = $directory.'/accounts.json';
        Nilai::creating(fn () => throw new \RuntimeException('PRIVATE-QA-FAILURE'));
        try {
            $this->assertSame(1, Artisan::call('uninalar:demo-prepare', ['--confirm-database' => $database, '--credentials-file' => $file]));
            $this->assertSame(0, User::count());
            $this->assertFileExists($file);
            $this->assertSame(0600, fileperms($file) & 0777);
            $this->assertStringNotContainsString('PRIVATE-QA-FAILURE', Artisan::output());
        } finally {
            Nilai::flushEventListeners();
            if (is_file($file)) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public function test_password_rotation_uses_hidden_input_and_rejects_non_demo_accounts(): void
    {
        $database = $this->configureProvisioning();
        app(HostingDemoProvisioner::class)->provision($database, $this->passwords());
        $new = Str::random(32);
        $this->artisan('uninalar:demo-password', ['username' => 'demo_mahasiswa', '--confirm-database' => $database])
            ->expectsQuestion('Password baru (minimal 24 karakter acak)', $new)
            ->expectsQuestion('Ulangi password baru', $new)->assertSuccessful();
        $this->assertTrue(Hash::check($new, User::where('username', 'demo_mahasiswa')->sole()->password));
        $this->artisan('uninalar:demo-password', ['username' => 'unknown', '--confirm-database' => $database])->assertFailed();
    }
}
