<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroqDemoBudget
{
    private function check(object $global, ?object $user): void
    {
        if (! app(GroqLivePolicy::class)->limitsValid()
            || $global->attempts >= (int) config('services.groq.demo.global_limit')
            || ($user->attempts ?? 0) >= (int) config('services.groq.demo.user_limit')) {
            throw new DomainException('Batas penggunaan AI demo tercapai. Riwayat tetap dapat dibaca.');
        }
        if ($global->lease_until > now()->timestamp) {
            throw new DomainException('Analisis sedang diproses. Periksa kembali hasil beberapa saat lagi.');
        }
        if ($user && $user->window_start > now()->timestamp - 60 && $user->window_attempts >= 2) {
            throw new DomainException('Batas permintaan tercapai. Tunggu satu menit sebelum mencoba kembali.');
        }
    }

    public function assertAvailable(int $userId): void
    {
        $global = DB::table('groq_demo_budgets')->where('id', 1)->first();
        if (! $global) {
            throw new DomainException('Kuota AI belum siap. Hubungi pengelola.');
        }
        $this->check($global, DB::table('groq_demo_user_budgets')->where('user_id', $userId)->first());
    }

    public function reserve(int $userId): string
    {
        return DB::transaction(function () use ($userId) {
            // A fixed row created by migration serializes ALL budget reservations.
            $global = DB::table('groq_demo_budgets')->where('id', 1)->lockForUpdate()->first();
            if (! $global) {
                throw new DomainException('Kuota AI belum siap. Hubungi pengelola.');
            }
            $user = DB::table('groq_demo_user_budgets')->where('user_id', $userId)->first();
            $this->check($global, $user);
            $token = Str::random(64);
            DB::table('groq_demo_budgets')->where('id', 1)->update([
                'attempts' => $global->attempts + 1, 'lease_token' => $token,
                'lease_until' => now()->timestamp + 120,
            ]);
            $sameWindow = $user && $user->window_start > now()->timestamp - 60;
            DB::table('groq_demo_user_budgets')->updateOrInsert(['user_id' => $userId], [
                'attempts' => ($user->attempts ?? 0) + 1,
                'window_start' => $sameWindow ? $user->window_start : now()->timestamp,
                'window_attempts' => $sameWindow ? $user->window_attempts + 1 : 1,
            ]);

            return $token;
        });
    }

    public function release(string $token): void
    {
        // A stale request cannot release a newer request's lease. Never refund attempts.
        DB::table('groq_demo_budgets')->where('id', 1)->where('lease_token', $token)
            ->update(['lease_token' => null, 'lease_until' => 0]);
    }

    public function assertLease(string $token): void
    {
        $global = DB::table('groq_demo_budgets')->where('id', 1)->lockForUpdate()->first();
        if (! $global || ! hash_equals((string) $global->lease_token, $token)
            || $global->lease_until <= now()->timestamp) {
            throw new DomainException('Waktu pemrosesan AI berakhir. Hasil baru tidak disimpan. Periksa riwayat sebelum mencoba lagi.');
        }
    }
}
