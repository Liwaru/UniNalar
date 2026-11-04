<?php

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// CLI fixtures for a verified disposable browser-QA database, never app startup.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$file = realpath((string) getenv('UNINALAR_QA_DATABASE'));
$temp = realpath(sys_get_temp_dir());
$password = (string) getenv('UNINALAR_QA_PASSWORD');
if (PHP_SAPI !== 'cli' || ! $app->environment('testing') || config('database.default') !== 'sqlite'
    || ! $file || ! $temp || dirname($file) !== $temp
    || ! preg_match('/^uninalar-qa-[a-f0-9]+\.sqlite$/', basename($file))
    || realpath(config('database.connections.sqlite.database')) !== $file || strlen($password) < 24) {
    throw new RuntimeException('Refusing fixture setup: temporary SQLite QA identity not verified.');
}
$connection = DB::connection();
$main = collect($connection->select('PRAGMA database_list'))->firstWhere('name', 'main');
if ($connection->getDriverName() !== 'sqlite' || realpath($main->file ?? '') !== $file
    || $connection->select("SELECT name FROM sqlite_master WHERE type = 'table'") !== []) {
    throw new RuntimeException('Refusing fixture setup: actual target must be the verified empty temporary file.');
}
echo 'Verified isolated target: sqlite / '.basename($file).PHP_EOL;
Artisan::call('migrate', ['--force' => true]);
$users = collect(['mahasiswa' => 1, 'dosen' => 2, 'admin' => 3, 'owner' => 4])->mapWithKeys(fn ($level, $role) => [
    $role => User::query()->create([
        'username' => 'qa_'.$role, 'name' => 'QA Terisolasi '.$role, 'email' => $role.'@qa.example.test',
        'password' => Hash::make($password), 'level' => $level,
    ]),
]);
$program = ProgramStudi::query()->create(['kode' => 'QA-TI', 'nama' => 'Teknologi Informasi — QA Terisolasi', 'jenjang' => 'S1', 'aktif' => true]);
$semester = Semester::query()->create([
    'kode' => 'QA-2026-1', 'tahun_ajaran' => '2026/2027', 'periode' => 1, 'aktif' => true,
    'krs_mulai_pada' => now('Asia/Jakarta')->subDay()->format('Y-m-d H:i:s'),
    'krs_selesai_pada' => now('Asia/Jakarta')->addDay()->format('Y-m-d H:i:s'),
]);
$lecturer = Dosen::query()->create(['user_id' => $users['dosen']->id, 'nama' => 'Dosen QA Terisolasi', 'program_studi_id' => $program->id, 'aktif' => true]);
Mahasiswa::query()->create(['user_id' => $users['mahasiswa']->id, 'nim' => 'QA-ISOLATED-001', 'nama' => 'Mahasiswa QA Terisolasi', 'program_studi_id' => $program->id, 'angkatan' => 2026]);
$course = MataKuliah::query()->create(['program_studi_id' => $program->id, 'kode' => 'QA101', 'nama' => 'Mata Kuliah QA Terisolasi', 'sks' => 3]);
foreach (range(1, 16) as $number) {
    KelasKuliah::query()->create(['mata_kuliah_id' => $course->id, 'semester_id' => $semester->id, 'dosen_id' => $lecturer->id, 'kode_kelas' => 'Q'.str_pad($number, 2, '0', STR_PAD_LEFT), 'kapasitas' => 30]);
}
echo 'Synthetic fixtures ready in disposable SQLite only.'.PHP_EOL;
