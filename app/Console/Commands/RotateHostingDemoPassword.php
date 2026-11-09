<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\HostingDemoProvisioner;
use Illuminate\Console\Command;

final class RotateHostingDemoPassword extends Command
{
    protected $signature = 'uninalar:demo-password {username} {--confirm-database=}';

    protected $description = 'Rotate one approved synthetic demo password using hidden input; no password arguments or logs.';

    public function handle(HostingDemoProvisioner $provisioner): int
    {
        try {
            $provisioner->assertTarget((string) $this->option('confirm-database'));
            $role = collect(HostingDemoProvisioner::ACCOUNTS)->search(fn ($account) => $account['username'] === $this->argument('username'));
            if ($role === false) {
                throw new \DomainException('Username bukan akun demo yang dikenal.');
            }
            $account = HostingDemoProvisioner::ACCOUNTS[$role];
            $user = User::where('username', $account['username'])->where('email', $role.'@example.test')->where('level', $account['level'])->sole();
            $password = $this->secret('Password baru (minimal 24 karakter acak)');
            $confirmation = $this->secret('Ulangi password baru');
            if (! is_string($password) || strlen($password) < 24 || strlen($password) > 128 || $password !== $confirmation) {
                throw new \DomainException('Password/konfirmasi tidak memenuhi syarat. Tidak ada perubahan.');
            }
            $user->forceFill(['password' => $password, 'remember_token' => null])->save();
            $this->info('Password demo diperbarui tanpa dicetak. Ganti nama SESSION_COOKIE di hosting untuk mengakhiri sesi existing, lalu perbarui config cache.');

            return self::SUCCESS;
        } catch (\DomainException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable) {
            $this->error('Password tidak dapat diperbarui. Periksa target demo secara privat; tidak ada detail sensitif dicetak.');
        }

        return self::FAILURE;
    }
}
