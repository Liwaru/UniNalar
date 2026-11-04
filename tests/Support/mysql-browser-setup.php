<?php

use App\Models\KelasKuliah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\MysqlFixtures;

$app = require __DIR__.'/mysql-bootstrap.php';
$password = getenv('QA_BROWSER_PASSWORD');
if (strlen($password) < 24) {
    throw new RuntimeException('A temporary browser credential is required');
}
$d = MysqlFixtures::make();
$users = ['mahasiswa' => $d['users'][0], 'dosen' => $d['lecturerUser'], 'admin' => $d['admin'], 'owner' => User::factory()->create(['level' => 4])];
foreach ($users as $role => $user) {
    $user->update(['username' => 'qa_'.$role, 'name' => 'QA Terisolasi '.$role, 'password' => Hash::make($password)]);
}
foreach (range(1, 16) as $number) {
    KelasKuliah::create(['mata_kuliah_id' => $d['course']->id, 'semester_id' => $d['semester']->id, 'dosen_id' => $d['lecturer']->id, 'kode_kelas' => 'QA'.$number, 'kapasitas' => 30]);
}
echo json_encode(['status' => 'Synthetic browser fixtures ready ONLY on QA', 'class_id' => $d['class']->id, 'course' => $d['course']->kode]).PHP_EOL;
