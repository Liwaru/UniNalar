<?php

namespace App\Services;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

final class GroqAcademicClient
{
    public function describe(array $indicators): array
    {
        app(GroqLivePolicy::class)->assertProvider();
        if (app()->environment('testing')) {
            Http::preventStrayRequests();
        }
        $base = config('services.groq.base_url');
        $model = config('services.groq.model');
        try {
            $response = Http::withToken(config('services.groq.key'))->acceptJson()
                ->connectTimeout(5)->timeout(25)->withoutRedirecting()
                ->post($base.'/chat/completions', [
                    'model' => $model, 'temperature' => 0.2, 'max_completion_tokens' => 900,
                    'reasoning_effort' => 'low', 'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => 'Jelaskan indikator akademik agregat dalam bahasa Indonesia. Jangan menetapkan skor, bobot, kategori risiko, diagnosis atau keputusan akademik. Jangan mengarang data; sebutkan keterbatasan data. Keluarkan JSON saja dengan tepat dua field: penjelasan (teks maksimal 1200 karakter) dan rekomendasi (1 sampai 3 teks, masing-masing maksimal 240 karakter). Tidak ada HTML, identitas atau field lain.'],
                        ['role' => 'user', 'content' => json_encode($indicators, JSON_THROW_ON_ERROR)],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new DomainException('Layanan AI tidak dapat dihubungi. Silakan coba lagi nanti.');
        }
        if (! $response->successful()) {
            throw new DomainException(match ($response->status()) {
                401, 403 => 'Akses layanan AI belum tersedia. Hubungi pengelola.',
                429 => 'Kuota layanan AI sedang terbatas. Silakan coba lagi nanti.',
                default => 'Layanan AI sedang tidak tersedia. Silakan coba lagi nanti.',
            });
        }
        if (strlen($response->body()) > 65536 || $response->json('choices.0.finish_reason') !== 'stop') {
            throw new DomainException('Respons AI tidak lengkap atau tidak valid.');
        }
        $content = $response->json('choices.0.message.content');
        $result = is_string($content) ? json_decode($content, true) : null;
        if (! is_array($result) || array_diff(array_keys($result), ['penjelasan', 'rekomendasi'])
            || Validator::make($result, [
                'penjelasan' => ['required', 'string', 'max:1200'],
                'rekomendasi' => ['required', 'array', 'list', 'min:1', 'max:3'],
                'rekomendasi.*' => ['required', 'string', 'max:240'],
            ])->fails()) {
            throw new DomainException('Format respons AI tidak valid. Hasil tidak disimpan.');
        }
        $usage = [];
        foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $key) {
            $value = $response->json('usage.'.$key);
            $usage[$key] = is_int($value) && $value >= 0 && $value <= 1000000 ? $value : null;
        }

        return $result + ['usage' => $usage, 'transport' => app(GroqLivePolicy::class)->transport()];
    }
}
