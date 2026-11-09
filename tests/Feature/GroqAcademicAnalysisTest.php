<?php

namespace Tests\Feature;

use App\Models\AnalisisRisiko;
use App\Models\Nilai;
use App\Models\User;
use App\Services\GroqAcademicAnalysis;
use App\Services\GroqAcademicClient;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\MysqlFixtures;
use Tests\TestCase;

class GroqAcademicAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.key' => 'synthetic-test-only', 'services.groq.live_enabled' => false]);
        Http::preventStrayRequests();
    }

    private function mockHttp($callback = null): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($callback);
    }

    private function fixture(): array
    {
        $d = MysqlFixtures::make(2);
        $detail = MysqlFixtures::detail($d);
        Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 78, 'nilai_huruf' => 'B', 'bobot' => 3,
            'diinput_oleh_user_id' => $d['lecturerUser']->id, 'final_at' => now(), 'difinalkan_oleh_user_id' => $d['lecturerUser']->id]);

        return $d;
    }

    private function response(array $result = [], string $finish = 'stop'): array
    {
        return ['choices' => [['finish_reason' => $finish, 'message' => ['content' => json_encode($result ?: [
            'penjelasan' => 'Satu nilai final tersedia. Kehadiran belum tercatat.',
            'rekomendasi' => ['Tinjau kembali materi kuliah.'],
        ])]]], 'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 80, 'total_tokens' => 200]];
    }

    public function test_analysis_form_exposes_only_readiness_and_never_calls_groq_on_get(): void
    {
        $d = $this->fixture();
        $this->mockHttp();
        $this->actingAs($d['users'][0]);
        $url = route('dashboard.mahasiswa.analisis-risiko');
        $this->get($url)->assertOk()->assertSee('data-ai-enabled="false"', false);
        config(['services.groq.live_enabled' => true, 'services.groq.model' => 'openai/gpt-oss-20b']);
        $this->get($url)->assertOk()->assertSee('data-ai-enabled="true"', false)
            ->assertDontSee('synthetic-test-only')->assertSee('name="persetujuan"', false);
        config(['services.groq.key' => null]);
        $this->get($url)->assertOk()->assertSee('data-ai-enabled="false"', false);
        config(['services.groq.key' => 'synthetic-test-only', 'services.groq.model' => 'unsupported-model']);
        $this->get($url)->assertOk()->assertSee('data-ai-enabled="false"', false);
        Http::assertNothingSent();
    }

    public function test_owned_results_are_persisted_reused_and_invalidated_without_pii_or_fabricated_scores(): void
    {
        $d = $this->fixture();
        $this->mockHttp(['api.groq.com/*' => Http::response($this->response())]);
        $service = app(GroqAcademicAnalysis::class);
        $first = $service->generate($d['profiles'][0], $d['semester']->id);
        $second = $service->generate($d['profiles'][0], $d['semester']->id);
        $this->assertSame($first->id, $second->id);
        Http::assertSentCount(1);
        $this->assertNull($first->skor);
        $this->assertNull($first->kategori);
        $this->assertSame('NEEDS SPECIFICATION', $first->ringkasan['klasifikasi']);
        $this->assertSame('mock', $first->ringkasan['transport']);
        $this->assertSame(200, $first->ringkasan['usage']['total_tokens']);
        $this->assertSame(1, $first->rekomendasiIntervensi()->count());
        Http::assertSent(function ($request) use ($d) {
            $indicators = json_decode($request['messages'][1]['content'], true);
            $this->assertSame(['mata_kuliah_disetujui', 'jumlah_nilai_final', 'rata_rata_nilai_final', 'kehadiran_tercatat', 'sks_disetujui', 'ips_semester', 'ipk_kumulatif'], array_keys($indicators));
            $this->assertSame(3, $indicators['sks_disetujui']);
            $this->assertSame(3, $indicators['ips_semester']);
            $this->assertSame(3, $indicators['ipk_kumulatif']);
            foreach (['nama', 'nim', 'email', 'no_telepon', 'mahasiswa_id', 'user_id'] as $key) {
                $this->assertArrayNotHasKey($key, $indicators);
            }
            $this->assertStringNotContainsString($d['profiles'][0]->nim, json_encode($request->data()));
            $this->assertSame(900, $request['max_completion_tokens']);

            return true;
        });
        Nilai::query()->update(['nilai_angka' => 80]);
        $third = $service->generate($d['profiles'][0], $d['semester']->id);
        $this->assertNotSame($first->id, $third->id);
        $this->assertSame(2, AnalisisRisiko::count());
        Http::assertSentCount(2);
    }

    public function test_gets_never_call_ai_and_student_owner_and_other_roles_are_scoped(): void
    {
        $d = $this->fixture();
        $this->mockHttp(['api.groq.com/*' => Http::response($this->response())]);
        $this->actingAs($d['users'][0]);
        $this->get(route('dashboard.mahasiswa'))->assertOk()->assertDontSee('synthetic-test-only');
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk();
        Http::assertNothingSent();
        $payload = ['semester_id' => $d['semester']->id, 'persetujuan' => 1];
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Analisis deskriptif berhasil disimpan.');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Hasil tersimpan digunakan kembali. Tidak ada permintaan AI baru.');
        $this->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertSee('Satu nilai final tersedia.');
        $this->actingAs($d['users'][1])->get(route('dashboard.mahasiswa.analisis-risiko'))->assertOk()->assertDontSee('Satu nilai final tersedia.');
        $owner = User::factory()->create(['level' => 4]);
        $this->actingAs($owner)->get(route('dashboard.owner.analisis-risiko.show', AnalisisRisiko::first()->id))->assertOk()->assertSee('Satu nilai final tersedia.');
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertForbidden();
        foreach ([$d['admin'], $d['lecturerUser']] as $other) {
            $this->actingAs($other)->post(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertForbidden();
        }
        Http::assertSentCount(1);
    }

    public function test_consent_semester_and_foreign_identifiers_are_validated_before_request(): void
    {
        $d = $this->fixture();
        $this->mockHttp();
        $this->actingAs($d['users'][0]);
        foreach ([[], ['semester_id' => -1, 'persetujuan' => 1], ['semester_id' => $d['semester']->id],
            ['semester_id' => $d['semester']->id, 'persetujuan' => 1, 'mahasiswa_id' => $d['profiles'][1]->id],
            ['semester_id' => $d['semester']->id, 'persetujuan' => 1, 'indikator' => ['nama' => 'PRIVATE']]] as $payload) {
            $this->postJson(route('dashboard.mahasiswa.analisis-risiko.ai'), $payload)->assertUnprocessable();
        }
        Http::assertNothingSent();
    }

    public function test_provider_errors_and_timeout_never_persist_or_leak_provider_body(): void
    {
        $d = $this->fixture();
        foreach ([401, 403, 429, 500, 503] as $status) {
            $this->mockHttp(['api.groq.com/*' => Http::response(['error' => 'PRIVATE-PROVIDER-DETAIL'], $status)]);
            Cache::flush();
            try {
                app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
                $this->fail('Provider failure accepted');
            } catch (DomainException $e) {
                $this->assertStringNotContainsString('PRIVATE', $e->getMessage());
            }
            $this->assertSame(0, AnalisisRisiko::count());
            Http::assertSentCount(1);
        }
        $this->mockHttp(fn () => throw new ConnectionException('PRIVATE-TRANSPORT-DETAIL'));
        Cache::flush();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Layanan AI tidak dapat dihubungi.');
        app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
    }

    public function test_invalid_truncated_and_excessive_outputs_are_rejected_without_storage(): void
    {
        $d = $this->fixture();
        foreach ([['penjelasan' => 'x', 'rekomendasi' => ['a'], 'skor' => 0.9],
            ['penjelasan' => 'x', 'rekomendasi' => ['a', 'b', 'c', 'd']],
            ['penjelasan' => str_repeat('x', 1201), 'rekomendasi' => ['a']],
            ['penjelasan' => ['wrong'], 'rekomendasi' => []]] as $bad) {
            Cache::flush();
            $this->mockHttp(['api.groq.com/*' => Http::response($this->response($bad))]);
            try {
                app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
                $this->fail('Invalid response accepted');
            } catch (DomainException) {
                $this->assertSame(0, AnalisisRisiko::count());
            }
        }
        Cache::flush();
        $this->mockHttp(['api.groq.com/*' => Http::response($this->response([], 'length'))]);
        $this->expectExceptionMessage('Respons AI tidak lengkap');
        app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
    }

    public function test_held_lock_prevents_duplicate_request_and_rate_limit_prevents_retry_storm(): void
    {
        $d = $this->fixture();
        $this->mockHttp(['api.groq.com/*' => Http::response(['error' => 'not available'], 429)]);
        $key = 'groq-academic:'.$d['profiles'][0]->id.':'.$d['semester']->id;
        $lock = Cache::lock($key, 90);
        $this->assertTrue($lock->get());
        try {
            app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
            $this->fail('Duplicate request accepted');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sedang diproses', $e->getMessage());
        } finally {
            $lock->release();
        }
        Http::assertNothingSent();
        for ($i = 0; $i < 3; $i++) {
            try {
                app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
            } catch (DomainException $e) {
                if ($i === 2) {
                    $this->assertStringContainsString('Batas permintaan', $e->getMessage());
                }
            }
        }
        Http::assertSentCount(2);
    }

    public function test_unapproved_live_requests_and_non_groq_endpoint_are_blocked(): void
    {
        $d = $this->fixture();
        $this->mockHttp();
        config(['services.groq.base_url' => 'http://localhost/private']);
        try {
            app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
            $this->fail('Untrusted provider accepted');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Konfigurasi', $e->getMessage());
        }
        Http::assertNothingSent();
        $this->app['env'] = 'local';
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('belum diizinkan');
        app(GroqAcademicClient::class)->describe([]);
    }

    public function test_login_filtered_navigation_and_saved_reuse_do_not_contact_provider(): void
    {
        $d = $this->fixture();
        $this->mockHttp(['api.groq.com/*' => Http::response($this->response())]);
        $analysis = app(GroqAcademicAnalysis::class)->generate($d['profiles'][0], $d['semester']->id);
        Http::assertSentCount(1);
        $this->mockHttp();
        $this->get(route('login'))->assertOk();
        $this->post(route('login.store'), ['username' => $d['users'][0]->username, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('dashboard.mahasiswa'));
        foreach (['dashboard.mahasiswa', 'dashboard.mahasiswa.analisis-risiko', 'dashboard.mahasiswa.rekomendasi',
            'dashboard.mahasiswa.nilai', 'dashboard.mahasiswa.kehadiran', 'dashboard.mahasiswa'] as $route) {
            $this->get(route($route, ['semester_id' => $d['semester']->id]))->assertOk();
        }
        $this->post(route('dashboard.mahasiswa.analisis-risiko.ai'), ['semester_id' => $d['semester']->id, 'persetujuan' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame($analysis->id, AnalisisRisiko::sole()->id);
        $owner = User::factory()->create(['level' => User::ROLE_KAPRODI]);
        $this->actingAs($owner);
        foreach (['dashboard.owner', 'dashboard.owner.analisis-risiko.index', 'dashboard.owner.rekomendasi.index'] as $route) {
            $this->get(route($route, ['semester_id' => $d['semester']->id]))->assertOk();
        }
        $this->get(route('dashboard.owner.analisis-risiko.show', $analysis->id))->assertOk();
        Http::assertNothingSent();
    }

    public function test_grade_indicators_exclude_drafts_unapproved_krs_and_other_students(): void
    {
        $d = $this->fixture();
        $other = MysqlFixtures::detail($d, 1);
        Nilai::create(['krs_detail_id' => $other->id, 'nilai_angka' => 100, 'nilai_huruf' => 'A',
            'diinput_oleh_user_id' => $d['lecturerUser']->id, 'final_at' => now(),
            'difinalkan_oleh_user_id' => $d['lecturerUser']->id]);
        $service = app(GroqAcademicAnalysis::class);
        $ownGrade = Nilai::where('krs_detail_id', '!=', $other->id)->firstOrFail();
        $valid = $service->indicators($d['profiles'][0], $d['semester']->id);
        $this->assertSame(78.0, $valid['rata_rata_nilai_final']);
        $this->assertSame(3.0, $valid['ips_semester']);
        $this->assertSame(3.0, $valid['ipk_kumulatif']);
        $this->assertSame(3, $valid['sks_disetujui']);
        $draftFixture = MysqlFixtures::make();
        $draftDetail = MysqlFixtures::detail($draftFixture);
        Nilai::create(['krs_detail_id' => $draftDetail->id, 'nilai_angka' => 99, 'nilai_huruf' => 'A',
            'diinput_oleh_user_id' => $draftFixture['lecturerUser']->id]);
        $draft = $service->indicators($draftFixture['profiles'][0], $draftFixture['semester']->id);
        $this->assertSame(0, $draft['jumlah_nilai_final']);
        $this->assertNull($draft['rata_rata_nilai_final']);
        $this->assertNull($draft['ips_semester']);
        $this->assertNull($draft['ipk_kumulatif']);
        $ownGrade->krsDetail->krs->update(['status' => 'draft']);
        $unapproved = $service->indicators($d['profiles'][0], $d['semester']->id);
        $this->assertSame(0, $unapproved['mata_kuliah_disetujui']);
        $this->assertSame(0, $unapproved['sks_disetujui']);
        $this->assertNull($unapproved['ips_semester']);
        Http::assertNothingSent();
    }

    public function test_unverified_runtime_is_denied_without_disclosing_details_or_calling_provider(): void
    {
        $d = $this->fixture();
        $this->mockHttp();
        $this->actingAs($d['users'][0]);
        $this->app['env'] = 'local';
        $this->withSession(['_token' => 'qa-guard-token'])->post(route('dashboard.mahasiswa.analisis-risiko.ai'), [
            '_token' => 'qa-guard-token', 'semester_id' => $d['semester']->id, 'persetujuan' => 1,
        ])->assertSessionHasErrors(['ai' => 'Environment analisis belum terverifikasi. Hubungi pengelola.']);
        Http::assertNothingSent();
        $this->assertSame(0, AnalisisRisiko::count());
    }
}
