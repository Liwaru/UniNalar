<?php

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\DevelopmentAcademicFixtures;
use App\Services\WslDevelopmentIdentity;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$m = WslDevelopmentIdentity::verify();
$owned = $m['academic_fixture'] ?? [];
$knownStudents = $m['synthetic_student_ids'];
$knownLecturers = Dosen::whereHas('user', fn ($q) => $q->where('username', 'wsl_dosen'))->pluck('id')->all();
if ($owned) {
    $knownLecturers[] = $owned['lecturer'];
}
if (Dosen::whereNotIn('id', $knownLecturers)->exists()) {
    throw new RuntimeException('Unknown lecturer data detected; fixture creation rejected.');
}
if (Mahasiswa::whereNotIn('id', $knownStudents)->exists()
    || User::whereNotIn('id', array_merge(
        Mahasiswa::whereIn('id', $knownStudents)->pluck('user_id')->all(),
        Dosen::pluck('user_id')->all(),
        User::whereIn('username', ['wsl_admin', 'wsl_owner'])->pluck('id')->all(),
        array_values($m['demo_accounts'] ?? []),
    ))->exists()) {
    throw new RuntimeException('Unknown data detected; fixture creation rejected.');
}
$admin = User::where('username', 'wsl_admin')->where('level', 3)->firstOrFail();
// Guard runs again before migration. No arbitrary artisan commands are accepted.
WslDevelopmentIdentity::verify();
Artisan::call('migrate', ['--force' => true]);
$fixture = app(DevelopmentAcademicFixtures::class)->create($admin, $owned);
$m['academic_fixture'] = $fixture;
$m['synthetic_student_ids'] = array_values(array_unique([...$knownStudents, $fixture['student']]));
file_put_contents(storage_path('app/private/wsl-dev-runtime.json'), json_encode($m, JSON_PRETTY_PRINT));
echo "Verified synthetic academic fixture available; no credentials printed.\n";
