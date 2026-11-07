<?php

use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\KrsSubmissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\MysqlFixtures;

$app = require __DIR__.'/mysql-bootstrap.php';
if (User::exists() || Schema::hasTable('qa_fixture_provenance')) {
    throw new RuntimeException('Phase 5 requires a fresh verified QA browser schema.');
}
$password = (string) getenv('QA_BROWSER_PASSWORD');
if (strlen($password) < 32) {
    throw new RuntimeException('Missing temporary QA browser credential.');
}
DB::transaction(function () use ($password): void {
    $data = MysqlFixtures::make(2);
    $owner = User::factory()->create(['name' => 'Arif Prasetyo', 'level' => 4]);
    foreach (['mahasiswa' => $data['users'][0], 'lain' => $data['users'][1], 'dosen' => $data['lecturerUser'], 'admin' => $data['admin'], 'owner' => $owner] as $role => $user) {
        $user->forceFill(['username' => 'qa_'.$role, 'email' => $role.'@qa.example.test', 'password' => Hash::make($password)])->save();
    }
    $data['semester']->forceFill(['tanggal_mulai' => '2026-08-03', 'tanggal_selesai' => '2027-01-16'])->save();
    foreach (['CS' => 'Cyber Security', 'RPL' => 'Rekayasa Perangkat Lunak'] as $code => $name) {
        ProgramStudi::create(['kode' => $code, 'nama' => $name, 'jenjang' => 'S1', 'aktif' => true]);
    }
    app(KrsSubmissionService::class)->addClass($data['users'][0], $data['class']->id);
});
Schema::create('qa_fixture_provenance', function (Blueprint $table): void {
    $table->id();
    $table->string('run_id');
    $table->string('table_name');
    $table->unsignedBigInteger('row_id');
    $table->string('source');
    $table->unique(['table_name', 'row_id']);
});
foreach (['users', 'program_studi', 'semester', 'dosen', 'mahasiswa', 'mata_kuliah', 'kelas_kuliah', 'krs', 'krs_detail'] as $table) {
    foreach (DB::table($table)->pluck('id') as $id) {
        DB::table('qa_fixture_provenance')->insert(['run_id' => getenv('QA_RUN_ID'), 'table_name' => $table, 'row_id' => $id, 'source' => 'synthetic_phase5']);
    }
}
echo json_encode(['source' => 'synthetic_phase5', 'run' => getenv('QA_RUN_ID'), 'users' => User::count(), 'profiles' => 2, 'provenance_rows' => DB::table('qa_fixture_provenance')->count()]).PHP_EOL;
