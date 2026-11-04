<?php

$app = require __DIR__.'/mysql-bootstrap.php';
use App\Services\GradeFinalizationService;
use App\Services\KrsSubmissionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$target = '2026_10_29_000000_prepare_krs_submission_history_and_grade_finalization';
$latest = DB::table('migrations')->orderByDesc('batch')->orderByDesc('id')->first();
if ($latest?->migration !== $target) {
    throw new RuntimeException('Refusing QA rollback: target is not the latest migration');
}
$before = DB::table('mahasiswa')->count();
$metadata = [];
foreach (['semester', 'krs', 'krs_pengajuan_riwayat', 'nilai'] as $table) {
    $metadata[$table] = ['columns' => Schema::getColumns($table), 'indexes' => Schema::getIndexes($table), 'foreign_keys' => Schema::getForeignKeys($table)];
}
$readyBefore = app(KrsSubmissionService::class)->workflowAvailable() && app(GradeFinalizationService::class)->workflowAvailable();
// A synthetic history timestamp is observed in two MySQL session zones.
$history = DB::table('krs_pengajuan_riwayat')->orderBy('id')->first();
$zones = [];
foreach (['+00:00', '+07:00'] as $zone) {
    DB::statement("SET time_zone = '$zone'");
    $zones[$zone] = $history ? DB::table('krs_pengajuan_riwayat')->where('id', $history->id)->value('diajukan_pada') : null;
}
DB::statement("SET time_zone = '+00:00'");
Artisan::call('migrate:rollback', ['--step' => 1, '--path' => 'database/migrations/'.$target.'.php', '--force' => true]);
$down = Artisan::output();
$absent = ! Schema::hasTable('krs_pengajuan_riwayat') && ! Schema::hasColumn('nilai', 'final_at') && ! Schema::hasColumn('semester', 'krs_mulai_pada') && ! Schema::hasColumn('krs', 'diputuskan_pada');
$unready = ! app(KrsSubmissionService::class)->workflowAvailable() && ! app(GradeFinalizationService::class)->workflowAvailable();
Artisan::call('migrate', ['--path' => 'database/migrations/'.$target.'.php', '--force' => true]);
$up = Artisan::output();
$readyAfter = app(KrsSubmissionService::class)->workflowAvailable() && app(GradeFinalizationService::class)->workflowAvailable();
$result = compact('metadata', 'zones', 'readyBefore', 'down', 'absent', 'unready', 'up', 'readyAfter');
$result['base_student_count_unchanged'] = $before === DB::table('mahasiswa')->count();
echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
if (! $readyBefore || ! $absent || ! $unready || ! $readyAfter || ! $result['base_student_count_unchanged']) {
    exit(1);
}
