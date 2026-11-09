<?php

namespace App\Services;

use App\Models\Mahasiswa;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GroqLivePolicy
{
    public function productionDemo(): bool
    {
        return app()->environment('production');
    }

    public function transport(): string
    {
        return app()->environment('testing') ? 'mock' : 'live';
    }

    public function assertAccess(Mahasiswa $student): void
    {
        if (app()->environment('testing')) {
            return;
        }
        if (app()->environment('local')) {
            try {
                $manifest = WslDevelopmentIdentity::verify();
            } catch (RuntimeException) {
                throw new DomainException('Environment analisis belum terverifikasi. Hubungi pengelola.');
            }
            if (! in_array((int) $student->id, $manifest['synthetic_student_ids'], true)) {
                throw new DomainException('Analisis baru hanya tersedia untuk fixture sintetis yang diverifikasi.');
            }

            return;
        }
        $demo = config('services.groq.demo', []);
        $connection = config('database.default');
        if (! $this->productionDemo() || ($demo['enabled'] ?? false) !== true
            || ($demo['mode'] ?? '') !== 'production-demo'
            || ! in_array(app()->environment(), $demo['environments'] ?? [], true)
            || config('app.debug') !== false || ! str_starts_with((string) config('app.url'), 'https://')
            || config('session.secure') !== true || config('session.http_only') !== true
            || ! in_array(config('session.driver'), ['file', 'database', 'redis', 'memcached'], true)
            || ! in_array(config('cache.default'), ['file', 'database', 'redis', 'memcached'], true)
            || ! in_array(config("database.connections.$connection.driver"), ['mysql', 'mariadb'], true)
            || config("database.connections.$connection.url")
            || ! is_string($demo['database'] ?? null) || trim($demo['database']) === ''
            || config("database.connections.$connection.database") !== $demo['database']
            || ! in_array((string) $student->user_id, $demo['user_ids'] ?? [], true)
            || ! $this->limitsValid()) {
            throw new DomainException('Analisis baru tidak tersedia karena konfigurasi atau izin demo. Riwayat tetap dapat dibaca.');
        }
        // Check the connected database, not only the configured name/driver.
        $db = DB::connection();
        if (! in_array($db->getDriverName(), ['mysql', 'mariadb'], true)
            || $db->selectOne('SELECT DATABASE() AS name')->name !== $demo['database']) {
            throw new DomainException('Database demo analisis belum terverifikasi. Hubungi pengelola.');
        }
    }

    public function limitsValid(): bool
    {
        foreach (['global_limit', 'user_limit'] as $name) {
            $value = config('services.groq.demo.'.$name);
            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1 || (int) $value > 10000) {
                return false;
            }
        }

        return true;
    }

    public function assertProvider(bool $forRequest = true): void
    {
        if (($forRequest && ! app()->environment('testing') || ! $forRequest)
            && config('services.groq.live_enabled') !== true) {
            throw new DomainException('Permintaan AI nyata belum diizinkan. Riwayat tetap dapat dibaca.');
        }
        if (! is_string(config('services.groq.key')) || trim(config('services.groq.key')) === ''
            || config('services.groq.base_url') !== 'https://api.groq.com/openai/v1'
            || config('services.groq.model') !== 'openai/gpt-oss-20b') {
            throw new DomainException('Konfigurasi layanan AI belum siap.');
        }
    }

    public function readiness(?Mahasiswa $student): array
    {
        try {
            if (! $student) {
                throw new DomainException('Profil mahasiswa tidak tersedia untuk analisis.');
            }
            $this->assertAccess($student);
            $this->assertProvider(false);
            if (RateLimiter::tooManyAttempts('groq-academic-user:'.$student->user_id, 2)) {
                throw new DomainException('Batas permintaan tercapai. Tunggu satu menit sebelum mencoba kembali.');
            }
            if ($this->productionDemo()) {
                app(GroqDemoBudget::class)->assertAvailable((int) $student->user_id);
            }

            return ['enabled' => true, 'message' => 'Siap dianalisis. Pilih semester dan berikan persetujuan.'];
        } catch (DomainException $e) {
            return ['enabled' => false, 'message' => $e->getMessage()];
        } catch (\Throwable) {
            return ['enabled' => false, 'message' => 'Analisis sementara tidak tersedia. Riwayat tetap dapat dibaca.'];
        }
    }
}
