<?php

namespace Tests\Feature;

use Database\Seeders\AcademicDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DemoSeederEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_does_not_insert_synthetic_records_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        app(DatabaseSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_academic_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);

        app(AcademicDemoSeeder::class)->run();
    }
}
