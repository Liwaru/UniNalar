<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

$app = require __DIR__.'/mysql-bootstrap.php';
if (User::count() !== 0 || Schema::hasTable('qa_fixture_provenance')) {
    throw new RuntimeException('Phase 3 browser setup requires a fresh isolated database');
}
$password = (string) getenv('QA_BROWSER_PASSWORD');
if (strlen($password) < 24) {
    throw new RuntimeException('Temporary browser credential missing');
}
Schema::create('qa_fixture_provenance', function (Blueprint $table): void {
    $table->id();
    $table->string('run_id');
    $table->string('table_name');
    $table->unsignedBigInteger('row_id');
    $table->string('source')->default('synthetic_phase3');
    $table->unique(['table_name', 'row_id']);
});
$names = ['mahasiswa' => ['Alya Rahma Putri', 1], 'dosen' => ['Fajar Aditya Nugraha', 2], 'admin' => ['Nadira Wulandari', 3], 'owner' => ['Arif Prasetyo', 4], 'lain' => ['Bima Satya Pradana', 2]];
foreach ($names as $role => [$name, $level]) {
    $user = User::create(['name' => $name, 'username' => 'qa_'.$role, 'email' => $role.'@qa.example.test', 'password' => Hash::make($password), 'level' => $level]);
    DB::table('qa_fixture_provenance')->insert(['run_id' => getenv('QA_RUN_ID'), 'table_name' => 'users', 'row_id' => $user->id, 'source' => 'synthetic_phase3']);
}
echo "Fresh synthetic accounts ready; academic master data must be entered through the QA UI.\n";
