<?php

use Illuminate\Support\Facades\DB;
use Tests\Support\IsolatedMysql;

$app = require __DIR__.'/mysql-bootstrap.php';
$tables = ['users', 'program_studi', 'semester', 'mata_kuliah', 'dosen', 'mahasiswa', 'kelas_kuliah', 'krs', 'krs_detail', 'krs_pengajuan_riwayat', 'nilai', 'kehadiran'];
$counts = [];
foreach ($tables as $table) {
    $ids = DB::table($table)->pluck('id');
    foreach ($ids as $id) {
        DB::table('qa_fixture_provenance')->updateOrInsert(['table_name' => $table, 'row_id' => $id], ['run_id' => getenv('QA_RUN_ID'), 'source' => 'synthetic_phase3']);
    }
    $counts[$table] = $ids->count();
}
echo json_encode(['identity' => IsolatedMysql::verify($app), 'counts' => $counts,
    'students' => DB::table('mahasiswa')->select('nim', 'nama', 'user_id')->get(),
    'histories' => DB::table('krs_pengajuan_riwayat')->select('krs_id', 'versi', 'status', 'snapshot_kelas')->get(),
    'grades' => DB::table('nilai')->select('nilai_angka', 'nilai_huruf', 'nilai_mutu', 'final_at')->get(),
    'provenance_rows' => DB::table('qa_fixture_provenance')->count()], JSON_PRETTY_PRINT).PHP_EOL;
