<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\RekomendasiIntervensi;
use App\Services\GroqAcademicAnalysis;
use App\Services\GroqDemoBudget;
use App\Services\GroqLivePolicy;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Symfony\Component\Process\Process;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class GroqProductionDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.groq.key' => 'synthetic-test-only', 'services.groq.live_enabled' => true,
            'services.groq.demo.global_limit' => 5, 'services.groq.demo.user_limit' => 3]);
    }

    private function response(): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
            'penjelasan' => 'Penjelasan sintetis.', 'rekomendasi' => ['Tinjau materi.'],
        ])]]]];
    }

    private function productionConfig(int $userId): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => false, 'app.url' => 'https://demo.example.test', 'session.secure' => true,
            'session.http_only' => true, 'session.driver' => 'file', 'cache.default' => 'file', 'database.default' => 'hosting',
            'database.connections.hosting' => ['driver' => 'mysql', 'database' => 'school_demo', 'url' => null],
            'services.groq.demo.enabled' => true, 'services.groq.demo.mode' => 'production-demo',
            'services.groq.demo.environments' => ['production'], 'services.groq.demo.database' => 'school_demo',
            'services.groq.demo.user_ids' => [(string) $userId]]);
    }

    private function fakeConnectedDatabase(string $database = 'school_demo'): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->andReturn('mysql');
        $connection->shouldReceive('selectOne')->with('SELECT DATABASE() AS name')->andReturn((object) ['name' => $database]);
        DB::shouldReceive('connection')->andReturn($connection);
    }

    private function assertDenied(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected fail-closed denial');
        } catch (DomainException) {
            Http::assertNothingSent();
        }
    }

    public function test_production_default_and_incomplete_config_are_denied_before_database_or_http(): void
    {
        $d = MysqlFixtures::make();
        $student = $d['profiles'][0];
        $this->app['env'] = 'production';
        $policy = app(GroqLivePolicy::class);
        $this->assertDenied(fn () => $policy->assertAccess($student));
        $this->productionConfig($student->user_id);
        foreach ([['services.groq.demo.enabled', false], ['services.groq.demo.mode', ''],
            ['services.groq.demo.environments', []], ['services.groq.demo.user_ids', []],
            ['services.groq.demo.database', 'other'], ['services.groq.demo.global_limit', 0],
            ['services.groq.demo.user_limit', -1], ['app.debug', true], ['app.url', 'http://demo.example.test'],
            ['session.secure', false], ['cache.default', 'array'], ['session.driver', 'array'], ['database.connections.hosting.driver', 'sqlite'],
            ['database.connections.hosting.url', 'mysql://not-allowed']] as [$key, $value]) {
            $before = config($key);
            config([$key => $value]);
            $this->assertDenied(fn () => $policy->assertAccess($student));
            config([$key => $before]);
        }
        $this->app['env'] = 'testing';
        config(['database.default' => 'sqlite']);
    }

    public function test_fully_explicit_production_policy_checks_actual_database_identity(): void
    {
        $d = MysqlFixtures::make();
        $student = $d['profiles'][0];
        $this->productionConfig($student->user_id);
        $manager = DB::getFacadeRoot();
        try {
            $this->fakeConnectedDatabase();
            app(GroqLivePolicy::class)->assertAccess($student);
            DB::swap($manager);
            $this->fakeConnectedDatabase('wrong_database');
            $this->assertDenied(fn () => app(GroqLivePolicy::class)->assertAccess($student));
            Http::assertNothingSent();
        } finally {
            DB::swap($manager);
            $this->app['env'] = 'testing';
            config(['database.default' => 'sqlite']);
        }
    }

    public function test_local_guard_cannot_fall_back_to_production_policy_and_ui_matches_denial(): void
    {
        $d = MysqlFixtures::make();
        $this->productionConfig($d['profiles'][0]->user_id);
        $this->app['env'] = 'local';
        $state = app(GroqLivePolicy::class)->readiness($d['profiles'][0]);
        $this->assertFalse($state['enabled']);
        $this->assertStringContainsString('belum terverifikasi', $state['message']);
        $this->assertDenied(fn () => app(GroqLivePolicy::class)->assertAccess($d['profiles'][0]));
        $this->app['env'] = 'testing';
        config(['database.default' => 'sqlite', 'services.groq.live_enabled' => false]);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.analisis-risiko'))
            ->assertOk()->assertSee('data-ai-enabled="false"', false)->assertDontSee('synthetic-test-only');
        Http::assertNothingSent();
    }

    public function test_unknown_environment_is_denied_even_with_demo_opt_in(): void
    {
        $d = MysqlFixtures::make();
        $this->productionConfig($d['profiles'][0]->user_id);
        $this->app['env'] = 'staging';
        $this->assertDenied(fn () => app(GroqLivePolicy::class)->assertAccess($d['profiles'][0]));
        $this->app['env'] = 'testing';
        config(['database.default' => 'sqlite']);
    }

    private function useProductionBudgetWithFakeTransport(): void
    {
        // Test seam only: runtime stays testing and ALL HTTP is faked. No production bypass in application.
        $this->app->instance(GroqLivePolicy::class, new class extends GroqLivePolicy
        {
            public function productionDemo(): bool
            {
                return true;
            }

            public function transport(): string
            {
                return 'live';
            }
        });
    }

    public function test_mock_cannot_replace_live_and_live_reuse_preserves_history_and_budget(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        Http::fake(['api.groq.com/*' => Http::response($this->response())]);
        $mock = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $this->assertSame('mock', $mock->ringkasan['transport']);
        $original = $mock->fresh()->getRawOriginal();
        $this->useProductionBudgetWithFakeTransport();
        $live = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $this->assertNotSame($mock->id, $live->id);
        $this->assertSame('live', $live->ringkasan['transport']);
        $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
        $again = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $this->assertSame($live->id, $again->id);
        $this->assertSame($original, $mock->fresh()->getRawOriginal());
        $this->assertSame(2, AnalisisRisiko::count());
        Http::assertSentCount(2);
        config(['services.groq.live_enabled' => false]);
        $this->assertFalse(app(GroqLivePolicy::class)->readiness($d['profiles'][0])['enabled']);
        $this->assertSame($live->id, app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id)->id);
        $this->actingAs($d['users'][0])->get(route('dashboard.mahasiswa.analisis-risiko'))
            ->assertOk()->assertSee('Penjelasan sintetis.');
        Http::assertSentCount(2);
    }

    public function test_completion_between_lookup_and_reservation_does_not_send_duplicate_http(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        Http::fake(['api.groq.com/*' => Http::response($this->response())]);
        $mock = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $attributes = $mock->only($mock->getFillable());
        $attributes['ringkasan']['transport'] = 'live';
        $this->useProductionBudgetWithFakeTransport();
        $this->app->instance(GroqDemoBudget::class, new class($attributes) extends GroqDemoBudget
        {
            public function __construct(private array $completed) {}

            public function reserve(int $userId): string
            {
                $token = parent::reserve($userId);
                AnalisisRisiko::create($this->completed);

                return $token;
            }
        });
        $completed = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $this->assertSame('live', $completed->ringkasan['transport']);
        $this->assertSame(2, AnalisisRisiko::count());
        Http::assertSentCount(1);
        $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
    }

    public function test_persistent_limits_survive_cache_clear_and_inflight_blocks_other_users(): void
    {
        config(['services.groq.demo.global_limit' => 2, 'services.groq.demo.user_limit' => 1]);
        $budget = app(GroqDemoBudget::class);
        $token = $budget->reserve(101);
        $this->assertDenied(fn () => $budget->reserve(102));
        $budget->release('wrong-token');
        $this->assertDenied(fn () => $budget->reserve(102));
        $budget->release($token);
        Cache::flush();
        $this->assertDenied(fn () => $budget->reserve(101));
        $second = $budget->reserve(102);
        $budget->release($second);
        $this->assertDenied(fn () => $budget->reserve(103));
        $this->assertSame(2, DB::table('groq_demo_budgets')->value('attempts'));
        $this->assertSame(2, DB::table('groq_demo_user_budgets')->sum('attempts'));
    }

    public function test_persistent_user_minute_limit_and_expired_lease_recover_without_refund(): void
    {
        config(['services.groq.demo.user_limit' => 5]);
        $budget = app(GroqDemoBudget::class);
        for ($i = 0; $i < 2; $i++) {
            $budget->release($budget->reserve(101));
        }
        Cache::flush();
        $this->assertDenied(fn () => $budget->reserve(101));
        $this->travel(61)->seconds();
        $old = $budget->reserve(101);
        $this->travel(121)->seconds();
        $new = $budget->reserve(102);
        $this->assertDenied(fn () => $budget->assertLease($old));
        $budget->release($old);
        $this->assertDenied(fn () => $budget->reserve(103));
        $budget->release($new);
        $this->assertSame(4, DB::table('groq_demo_budgets')->value('attempts'));
    }

    public function test_provider_failure_charges_once_releases_slot_and_never_retries(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        $this->useProductionBudgetWithFakeTransport();
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'PRIVATE'], 503)]);
        $this->actingAs($d['users'][0])->post(route('dashboard.mahasiswa.analisis-risiko.ai'),
            ['semester_id' => $d['semester']->id, 'persetujuan' => 1])->assertSessionHasErrors('ai');
        $this->assertSame(0, AnalisisRisiko::count());
        $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
        $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
        $this->assertTrue(app(GroqLivePolicy::class)->readiness($d['profiles'][0])['enabled']);
        Http::assertSentCount(1);
    }

    public function test_successful_response_then_child_write_failure_rolls_back_without_touching_history(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        Http::fake(['api.groq.com/*' => Http::response($this->response())]);
        $historical = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        $original = $historical->fresh()->getRawOriginal();
        $this->useProductionBudgetWithFakeTransport();
        RekomendasiIntervensi::creating(fn () => DB::statement('INSERT INTO qa_missing_table VALUES (1)'));
        try {
            $this->actingAs($d['users'][0])->post(route('dashboard.mahasiswa.analisis-risiko.ai'),
                ['semester_id' => $d['semester']->id, 'persetujuan' => 1])->assertSessionHasErrors([
                    'ai' => 'Respons AI diterima tetapi hasil gagal disimpan. Tidak ada hasil baru yang tersimpan. Periksa riwayat sebelum mencoba lagi; permintaan tidak diulang otomatis.',
                ]);
            $this->assertSame(1, AnalisisRisiko::count());
            $this->assertSame(1, RekomendasiIntervensi::count());
            $this->assertSame($original, $historical->fresh()->getRawOriginal());
            $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
            $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
            Http::assertSentCount(2);
        } finally {
            RekomendasiIntervensi::flushEventListeners();
        }
    }

    public function test_expired_production_lease_never_saves_late_response_or_refunds_attempt(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        $this->useProductionBudgetWithFakeTransport();
        Http::fake(function () {
            $this->travel(121)->seconds();

            return Http::response($this->response());
        });
        $this->actingAs($d['users'][0])->post(route('dashboard.mahasiswa.analisis-risiko.ai'),
            ['semester_id' => $d['semester']->id, 'persetujuan' => 1])->assertSessionHasErrors('ai');
        $this->assertSame(0, AnalisisRisiko::count());
        $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
        $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
        Http::assertSentCount(1);
    }

    public function test_two_real_processes_cannot_exceed_persistent_global_budget(): void
    {
        if (! str_starts_with(base_path(), '/tmp/uninalar-') || is_file(base_path('.env'))) {
            $this->markTestSkipped('Standalone worker requires the isolated /tmp QA root without dotenv.');
        }
        $file = '/tmp/groq-budget-'.bin2hex(random_bytes(8)).'.sqlite';
        touch($file);
        chmod($file, 0600);
        config(['database.connections.budget_qa' => ['driver' => 'sqlite', 'database' => $file, 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('budget_qa');
        try {
            (require database_path('migrations/2026_11_10_000000_create_groq_demo_budgets.php'))->up();
        } finally {
            DB::setDefaultConnection('sqlite');
        }
        $start = (string) (microtime(true) + 0.6);
        $processes = [];
        try {
            foreach ([101, 102] as $id) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/groq-budget-worker.php'), $file, (string) $id, $start], base_path(), [
                    'APP_BASE_PATH' => base_path(), 'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite',
                    'DB_DATABASE' => ':memory:', 'DB_URL' => '', 'GROQ_API_KEY' => 'synthetic-test-only',
                    'GROQ_LIVE_ENABLED' => 'false', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array',
                ]);
                $process->start();
                $processes[] = $process;
            }
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
                $results[] = $process->getOutput();
            }
            sort($results);
            $this->assertSame(['blocked', 'reserved'], $results);
            $this->assertSame(1, DB::connection('budget_qa')->table('groq_demo_budgets')->value('attempts'));
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
            DB::purge('budget_qa');
            unlink($file);
        }
    }

    public function test_missing_budget_store_fails_closed_and_ui_backend_both_refuse(): void
    {
        $d = MysqlFixtures::make();
        MysqlFixtures::detail($d);
        $this->useProductionBudgetWithFakeTransport();
        DB::table('groq_demo_budgets')->delete();
        $this->assertFalse(app(GroqLivePolicy::class)->readiness($d['profiles'][0])['enabled']);
        $this->actingAs($d['users'][0])->post(route('dashboard.mahasiswa.analisis-risiko.ai'),
            ['semester_id' => $d['semester']->id, 'persetujuan' => 1])->assertSessionHasErrors('ai');
        Http::assertNothingSent();
    }
}
