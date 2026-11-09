<?php

namespace Tests\Mysql;

use App\Models\AnalisisRisiko;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\User;
use App\Services\GroqAcademicAnalysis;
use App\Services\GroqDemoBudget;
use App\Services\GroqLivePolicy;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

class GroqHostingTest extends MysqlTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.groq.key' => 'synthetic-qa-only', 'services.groq.live_enabled' => false]);
    }

    protected function tearDown(): void
    {
        $this->app['env'] = 'testing';
        parent::tearDown();
    }

    private function fixture(): array
    {
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 78, 'nilai_huruf' => 'B', 'bobot' => 3,
            'final_at' => now(), 'diinput_oleh_user_id' => $d['lecturerUser']->id, 'difinalkan_oleh_user_id' => $d['lecturerUser']->id]);

        return $d;
    }

    private function production(array $d): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => false, 'app.url' => 'https://demo.example.test', 'session.secure' => true,
            'session.http_only' => true, 'session.driver' => 'file', 'cache.default' => 'file',
            'services.groq.live_enabled' => true, 'services.groq.demo.enabled' => true,
            'services.groq.demo.mode' => 'production-demo', 'services.groq.demo.environments' => ['production'],
            'services.groq.demo.database' => config('database.connections.mysql.database'),
            'services.groq.demo.user_ids' => [(string) $d['users'][0]->id],
            'services.groq.demo.global_limit' => 20, 'services.groq.demo.user_limit' => 10]);
    }

    private function response(): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
            'penjelasan' => 'Penjelasan sintetis MySQL. Kehadiran belum tercatat.',
            'rekomendasi' => ['Tinjau materi.'],
        ])]]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30]];
    }

    private function fake(mixed $response = null): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($response ?? ['api.groq.com/*' => Http::response($this->response())]);
    }

    private function denied(callable $callback, ?string $message = null): void
    {
        try {
            $callback();
            $this->fail('Expected denial before provider/storage');
        } catch (DomainException $e) {
            if ($message !== null) {
                $this->assertStringContainsString($message, $e->getMessage());
            } else {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_actual_mysql_production_policy_default_off_incomplete_wrong_owner_and_local_guard(): void
    {
        $d = $this->fixture();
        $this->app['env'] = 'production';
        $policy = app(GroqLivePolicy::class);
        $this->assertFalse($policy->readiness($d['profiles'][0])['enabled']);
        $this->denied(fn () => $policy->assertAccess($d['profiles'][0]));
        $this->production($d);
        $policy->assertAccess($d['profiles'][0]);
        $this->assertTrue($policy->readiness($d['profiles'][0])['enabled']);
        $this->denied(fn () => $policy->assertAccess($d['profiles'][1]));
        foreach (['services.groq.demo.enabled' => false, 'services.groq.demo.mode' => '',
            'services.groq.demo.environments' => [], 'services.groq.demo.user_ids' => [],
            'services.groq.demo.database' => 'qa_wrong', 'services.groq.demo.global_limit' => 0,
            'app.debug' => true, 'session.secure' => false] as $key => $bad) {
            $before = config($key);
            config([$key => $bad]);
            $this->denied(fn () => $policy->assertAccess($d['profiles'][0]));
            $this->assertFalse($policy->readiness($d['profiles'][0])['enabled']);
            config([$key => $before]);
        }
        $this->app['env'] = 'local';
        $this->denied(fn () => $policy->assertAccess($d['profiles'][0]), 'belum terverifikasi');
        Http::assertNothingSent();
    }

    public function test_mysql_json_live_mock_separation_reuse_history_and_read_only_navigation(): void
    {
        $d = $this->fixture();
        $this->fake();
        $service = app(GroqAcademicAnalysis::class);
        $mock = $service->generate($d['profiles'][0], $d['semester']->id);
        $original = $mock->fresh()->getRawOriginal();
        $this->production($d);
        $live = $service->generate($d['profiles'][0], $d['semester']->id);
        $this->assertNotSame($mock->id, $live->id);
        $this->assertSame('live', $live->ringkasan['transport']);
        $this->assertSame('mock', $mock->ringkasan['transport']);
        $this->assertNull($live->skor);
        $this->assertNull($live->kategori);
        $this->assertSame(30, $live->ringkasan['usage']['total_tokens']);
        $this->assertSame(1, $live->rekomendasiIntervensi()->count());
        $this->assertSame($live->id, $service->generate($d['profiles'][0], $d['semester']->id)->id);
        $this->assertEquals($original, $mock->fresh()->getRawOriginal());
        $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
        config(['services.groq.live_enabled' => false]);
        $this->assertFalse(app(GroqLivePolicy::class)->readiness($d['profiles'][0])['enabled']);
        $this->assertSame($live->id, $service->generate($d['profiles'][0], $d['semester']->id)->id);
        Http::assertSentCount(2);
        $this->app['env'] = 'testing';
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertSee('Penjelasan sintetis MySQL.');
        $this->actingAs($d['users'][1])->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertDontSee('Penjelasan sintetis MySQL.');
        foreach ([$d['admin'], $d['lecturerUser']] as $actor) {
            $this->actingAs($actor)->post(route('dashboard.mahasiswa.analisis-risiko.ai'), ['semester_id' => $d['semester']->id, 'persetujuan' => 1])->assertForbidden();
        }
        Http::assertSentCount(2);
    }

    public function test_mysql_provider_failures_timeout_invalid_output_charge_once_and_retry_remains_bounded(): void
    {
        $d = $this->fixture();
        $this->production($d);
        $service = app(GroqAcademicAnalysis::class);
        $attempts = 0;
        foreach ([401, 403, 429, 500, 503, 'timeout', 'invalid'] as $failure) {
            Cache::flush();
            $this->travel(61)->seconds();
            $this->fake($failure === 'timeout'
                ? fn () => throw new ConnectionException('PRIVATE-TRANSPORT')
                : ['api.groq.com/*' => Http::response($failure === 'invalid' ? ['choices' => []] : ['error' => 'PRIVATE-BODY'], is_int($failure) ? $failure : 200)]);
            try {
                $service->generate($d['profiles'][0], $d['semester']->id);
                $this->fail('Invalid provider result accepted');
            } catch (DomainException $e) {
                $this->assertStringNotContainsString('PRIVATE', $e->getMessage());
            }
            $attempts++;
            $this->assertSame($attempts, DB::table('groq_demo_budgets')->value('attempts'));
            $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
            $this->assertSame(0, AnalisisRisiko::where('mahasiswa_id', $d['profiles'][0]->id)->count());
            if ($failure !== 'timeout') {
                Http::assertSentCount(1);
            }
        }
        Cache::flush();
        $this->travel(61)->seconds();
        $this->fake();
        $saved = $service->generate($d['profiles'][0], $d['semester']->id);
        $this->assertSame('live', $saved->ringkasan['transport']);
        $this->assertSame(8, DB::table('groq_demo_budgets')->value('attempts'));
        Http::assertSentCount(1);
    }

    public function test_mysql_parent_child_transaction_rolls_back_after_successful_provider_response(): void
    {
        $d = $this->fixture();
        $this->fake();
        $old = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $original = $old->fresh()->getRawOriginal();
        $this->production($d);
        RekomendasiIntervensi::creating(fn () => DB::statement('INSERT INTO qa_missing_table VALUES (1)'));
        try {
            $this->denied(fn () => app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id), 'gagal disimpan');
            $this->assertSame(1, AnalisisRisiko::where('mahasiswa_id', $d['profiles'][0]->id)->count());
            $this->assertSame(1, $old->rekomendasiIntervensi()->count());
            $this->assertEquals($original, $old->fresh()->getRawOriginal());
            $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
            $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
            Http::assertSentCount(2);
        } finally {
            RekomendasiIntervensi::flushEventListeners();
        }
    }

    public function test_mysql_persistent_user_global_limits_and_lease_owner_survive_cache_clear(): void
    {
        config(['services.groq.demo.global_limit' => 2, 'services.groq.demo.user_limit' => 1]);
        $budget = app(GroqDemoBudget::class);
        $token = $budget->reserve(700001);
        $this->denied(fn () => $budget->reserve(700002), 'sedang diproses');
        $budget->release('wrong-owner');
        $this->denied(fn () => $budget->reserve(700002), 'sedang diproses');
        $budget->release($token);
        Cache::flush();
        $this->denied(fn () => $budget->reserve(700001), 'Batas penggunaan');
        $budget->release($budget->reserve(700002));
        $this->denied(fn () => $budget->reserve(700003), 'Batas penggunaan');
        $this->assertSame(2, DB::table('groq_demo_budgets')->value('attempts'));
        Http::assertNothingSent();
    }

    public function test_mysql_persistent_minute_window_and_expired_lease_do_not_refund_or_release_new_owner(): void
    {
        config(['services.groq.demo.global_limit' => 10, 'services.groq.demo.user_limit' => 10]);
        $budget = app(GroqDemoBudget::class);
        for ($i = 0; $i < 2; $i++) {
            $budget->release($budget->reserve(700004));
        }
        Cache::flush();
        $this->denied(fn () => $budget->reserve(700004), 'Tunggu satu menit');
        $this->travel(61)->seconds();
        $old = $budget->reserve(700004);
        $this->travel(121)->seconds();
        $new = $budget->reserve(700005);
        $this->denied(fn () => $budget->assertLease($old), 'Waktu pemrosesan');
        $budget->release($old);
        $this->assertSame($new, DB::table('groq_demo_budgets')->value('lease_token'));
        $budget->release($new);
        $this->assertSame(4, DB::table('groq_demo_budgets')->value('attempts'));
        Http::assertNothingSent();
    }

    public function test_actual_production_form_readiness_consent_csrf_save_reuse_and_owner_history(): void
    {
        $d = $this->fixture();
        $this->production($d);
        $this->fake();
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.analisis-risiko'))
            ->assertOk()->assertSee('data-ai-enabled="true"', false)->assertDontSee('synthetic-qa-only');
        Http::assertNothingSent();
        $token = 'synthetic-qa-csrf-token';
        $payload = ['_token' => $token, 'semester_id' => $d['semester']->id, 'persetujuan' => 1];
        $this->withSession(['_token' => $token])->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', 'Analisis deskriptif berhasil disimpan.');
        $analysis = AnalisisRisiko::where('mahasiswa_id', $d['profiles'][0]->id)->sole();
        $this->assertSame('live', $analysis->ringkasan['transport']);
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertSee('Penjelasan sintetis MySQL.');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'Hasil tersimpan digunakan kembali. Tidak ada permintaan AI baru.');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), ['_token' => $token, 'semester_id' => $d['semester']->id])
            ->assertSessionHasErrors('persetujuan');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), array_replace($payload, ['_token' => 'wrong-token']))
            ->assertStatus(419);
        $owner = User::factory()->create(['level' => 4]);
        $this->actingAs($owner)->get(route('dashboard.owner.analisis-risiko.show', $analysis->id))
            ->assertOk()->assertSee('Penjelasan sintetis MySQL.');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertForbidden();
        $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
        $this->assertSame(1, AnalisisRisiko::where('mahasiswa_id', $d['profiles'][0]->id)->count());
        Http::assertSentCount(1);
    }
}
