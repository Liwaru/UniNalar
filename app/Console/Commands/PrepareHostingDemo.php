<?php

namespace App\Console\Commands;

use App\Services\HostingDemoProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class PrepareHostingDemo extends Command
{
    protected $signature = 'uninalar:demo-prepare {--confirm-database= : Exact new demo database name} {--credentials-file= : New private file outside the application and public root}';

    protected $description = 'Initialize a new, explicitly approved synthetic hosting demo; never overwrite existing data.';

    public function handle(HostingDemoProvisioner $provisioner): int
    {
        $handle = null;
        try {
            $database = (string) $this->option('confirm-database');
            $provisioner->assertTarget($database);
            $requested = (string) $this->option('credentials-file');
            $directory = realpath(dirname($requested));
            if (! str_starts_with($requested, '/') || ! $directory || (fileperms($directory) & 0077) !== 0
                || basename($requested) === '.' || basename($requested) === '..') {
                throw new \DomainException('File credentials memerlukan direktori privat 0700 dan path absolut baru.');
            }
            $file = $directory.'/'.basename($requested);
            foreach ([realpath(base_path()), realpath(public_path())] as $root) {
                if ($root && ($file === $root || str_starts_with($file, $root.'/'))) {
                    throw new \DomainException('Credentials harus disimpan di luar root aplikasi dan document root publik.');
                }
            }
            $handle = @fopen($file, 'x');
            if ($handle === false || ! chmod($file, 0600)) {
                throw new \DomainException('File credentials sudah ada atau tidak dapat dibuat privat; tidak ada overwrite.');
            }
            $passwords = [];
            $credentials = [];
            foreach (HostingDemoProvisioner::ACCOUNTS as $role => $account) {
                $passwords[$role] = Str::random(32);
                $credentials[$role] = ['username' => $account['username'], 'password' => $passwords[$role]];
            }
            $json = json_encode($credentials, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
            if (fwrite($handle, $json) !== strlen($json) || ! fflush($handle)) {
                throw new \DomainException('Penyimpanan credentials privat gagal; database belum diinisialisasi.');
            }
            fclose($handle);
            $handle = null;
            $result = $provisioner->provision($database, $passwords);
            $this->info('Dataset sintetis dan empat akun dibuat. Password hanya ada di file privat; jangan unggah/bagikan melalui chat.');
            $this->line('User ID mahasiswa untuk allowlist Groq nanti: '.$result['user_ids']['mahasiswa']);
            $this->line('Matikan provisioning, perbarui config cache, dan ikuti alur KRS draft sebelum analisis semester aktif.');

            return self::SUCCESS;
        } catch (\DomainException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable) {
            $this->error('Provisioning gagal atau hasil penyimpanan belum pasti. Periksa database demo secara privat; jangan ulang otomatis.');
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
        $this->warn('Jika file credentials baru sudah dibuat, simpan privat untuk pemeriksaan; file dan data existing tidak dihapus.');

        return self::FAILURE;
    }
}
