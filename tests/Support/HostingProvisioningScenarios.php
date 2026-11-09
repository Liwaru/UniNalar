<?php

namespace Tests\Support;

use App\Models\AnalisisRisiko;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\User;
use App\Services\AcademicGradeSummary;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\HostingDemoProvisioner;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use App\Services\OperationalAcademicData;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait HostingProvisioningScenarios
{
    private function configureProvisioning(): string
    {
        Http::preventStrayRequests();
        $database = DB::connection()->getDatabaseName();
        config(['hosting_demo.enabled' => true, 'hosting_demo.mode' => 'production-demo', 'hosting_demo.database' => $database,
            'services.groq.live_enabled' => false, 'services.groq.demo.enabled' => false]);
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->app['env'] = 'production';
            config(['app.debug' => false, 'app.url' => 'https://demo.example.test']);
        }

        return $database;
    }

    private function passwords(): array
    {
        return collect(HostingDemoProvisioner::ACCOUNTS)->map(fn () => Str::random(32))->all();
    }

    public function test_initializer_is_default_off_and_rejects_wrong_target_or_live_mode(): void
    {
        Http::preventStrayRequests();
        $service = app(HostingDemoProvisioner::class);
        try {
            $service->assertTarget('not-approved');
            $this->fail('Default provisioning accepted');
        } catch (DomainException) {
            $this->assertSame(0, User::count());
        }
        $database = $this->configureProvisioning();
        foreach (['wrong-database', ''] as $wrong) {
            try {
                $service->provision($wrong, $this->passwords());
                $this->fail('Wrong target accepted');
            } catch (DomainException) {
                $this->assertSame(0, User::count());
            }
        }
        config(['services.groq.live_enabled' => true]);
        $this->expectException(DomainException::class);
        $service->provision($database, $this->passwords());
    }

    public function test_local_environment_is_rejected_even_with_explicit_hosting_opt_in(): void
    {
        $database = $this->configureProvisioning();
        $this->app['env'] = 'local';
        try {
            app(HostingDemoProvisioner::class)->provision($database, $this->passwords());
            $this->fail('Development runtime was provisioned');
        } catch (DomainException) {
            $this->assertSame(0, User::count());
        }
        Http::assertNothingSent();
    }

    public function test_initializer_creates_coherent_synthetic_data_and_never_overwrites_existing_history(): void
    {
        $database = $this->configureProvisioning();
        $passwords = $this->passwords();
        $result = app(HostingDemoProvisioner::class)->provision($database, $passwords);
        $this->assertSame(4, User::count());
        foreach (HostingDemoProvisioner::ACCOUNTS as $role => $account) {
            $user = User::where('username', $account['username'])->sole();
            $this->assertSame($account['level'], $user->level);
            $this->assertTrue(Hash::check($passwords[$role], $user->password));
            $this->assertNotSame($passwords[$role], $user->password);
        }
        $this->assertSame(1, OperationalAcademicData::students()->count());
        $this->assertSame(1, OperationalAcademicData::lecturers()->count());
        $this->assertSame(6, OperationalAcademicData::classes()->count());
        $student = Mahasiswa::findOrFail($result['student_id']);
        $this->assertSame(3.5, app(AcademicGradeSummary::class)->forStudent($student)['ipk']);
        $this->assertSame(3, Nilai::whereNotNull('final_at')->count());
        $this->assertSame('mock', AnalisisRisiko::sole()->ringkasan['transport']);
        $this->assertSame('hosting_demo_fixture', AnalisisRisiko::sole()->ringkasan['sumber']);
        $this->assertSame(1, OperationalAcademicData::analyses()->count());
        $before = User::orderBy('id')->get()->map->getRawOriginal()->all();
        try {
            app(HostingDemoProvisioner::class)->provision($database, $this->passwords());
            $this->fail('Existing database overwritten');
        } catch (DomainException $e) {
            $this->assertStringContainsString('harus kosong', $e->getMessage());
        }
        $this->assertEquals($before, User::orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertSame(1, AnalisisRisiko::count());
        Http::assertNothingSent();
    }

    public function test_seeded_active_draft_can_submit_approve_and_finalize_using_existing_services(): void
    {
        $database = $this->configureProvisioning();
        $result = app(HostingDemoProvisioner::class)->provision($database, $this->passwords());
        $student = User::findOrFail($result['user_ids']['mahasiswa']);
        $admin = User::findOrFail($result['user_ids']['admin']);
        $lecturer = User::findOrFail($result['user_ids']['dosen']);
        $history = app(KrsSubmissionService::class)->submit($student);
        $this->assertSame('menunggu', $history->status);
        app(KrsDecisionService::class)->decide($history->id, $admin, 'disetujui', 'Persetujuan fixture QA');
        $krs = Krs::where('semester_id', $result['active_semester_id'])->sole();
        $grade = app(GradeEntryService::class)->save($lecturer, $krs->details()->firstOrFail()->id, ['nilai_angka' => 90, 'nilai_huruf' => 'A']);
        app(GradeFinalizationService::class)->finalize($lecturer, $grade->id);
        $summary = app(AcademicGradeSummary::class)->forStudent($student->mahasiswa);
        $this->assertSame(4.0, $summary['ips']);
        $this->assertSame(3.63, $summary['ipk']);
        Http::assertNothingSent();
    }

    public function test_weak_or_duplicate_passwords_are_rejected_before_any_data_write(): void
    {
        $database = $this->configureProvisioning();
        foreach ([array_fill_keys(array_keys(HostingDemoProvisioner::ACCOUNTS), 'weak'),
            array_fill_keys(array_keys(HostingDemoProvisioner::ACCOUNTS), Str::random(32))] as $passwords) {
            try {
                app(HostingDemoProvisioner::class)->provision($database, $passwords);
                $this->fail('Weak/duplicate passwords accepted');
            } catch (DomainException) {
                $this->assertSame(0, User::count());
            }
        }
    }
}
