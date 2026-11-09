<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'username' => 'test_admin', 'password' => Str::random(64), 'level' => User::ROLE_ADMIN_AKADEMIK],
        );

        $this->call(AcademicDemoSeeder::class);
    }
}
