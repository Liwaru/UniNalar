<?php

use App\Models\Nilai;
use App\Models\User;
use App\Services\WslDevelopmentIdentity;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Tests\Support\MysqlFixtures;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$m = WslDevelopmentIdentity::verify();
if (! $m['initialized'] || User::count() !== 0) {
    throw new RuntimeException('Fixture creation requires initialized empty isolated development database.');
}
$data = MysqlFixtures::make(2);
$credentials = [];
foreach ([$data['users'][0], $data['lecturerUser'], $data['admin'], User::factory()->create(['level' => 4])] as $i => $user) {
    $password = bin2hex(random_bytes(12));
    $user->update(['username' => 'wsl_'.['mahasiswa', 'dosen', 'admin', 'owner'][$i], 'password' => Hash::make($password)]);
    $credentials[] = ['username' => $user->username, 'password' => $password];
}
$detail = MysqlFixtures::detail($data);
Nilai::create(['krs_detail_id' => $detail->id, 'nilai_angka' => 78, 'nilai_huruf' => 'B', 'bobot' => 3,
    'diinput_oleh_user_id' => $data['lecturerUser']->id, 'final_at' => now(), 'difinalkan_oleh_user_id' => $data['lecturerUser']->id]);
$m['synthetic_student_ids'] = $data['profiles']->pluck('id')->map(fn ($id) => (int) $id)->all();
file_put_contents(storage_path('app/private/wsl-dev-runtime.json'), json_encode($m, JSON_PRETTY_PRINT));
$file = storage_path('app/private/wsl-dev-credentials.json');
file_put_contents($file, json_encode($credentials, JSON_PRETTY_PRINT));
chmod($file, 0600);
echo "Only synthetic fixtures created. Local credentials: storage/app/private/wsl-dev-credentials.json (not printed).\n";
