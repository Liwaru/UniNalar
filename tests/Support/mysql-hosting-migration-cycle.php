<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require __DIR__.'/mysql-bootstrap.php';
if (DB::table('users')->exists() || DB::table('analisis_risiko')->exists()
    || DB::table('groq_demo_budgets')->where('attempts', '>', 0)->exists()) {
    throw new RuntimeException('Migration cycle requires the disposable QA schema before fixtures.');
}
$count = DB::table('migrations')->count();
if ($count !== count(glob(database_path('migrations/*.php')))) {
    throw new RuntimeException('All current migrations must have completed first.');
}
Artisan::call('migrate:rollback', ['--step' => $count, '--force' => true]);
if (Schema::hasTable('users') || Schema::hasTable('analisis_risiko') || Schema::hasTable('groq_demo_budgets')) {
    throw new RuntimeException('Expected application tables removed only in disposable QA schema.');
}
echo 'ROLLBACK_EMPTY_QA PASS ('.$count." migrations)\n";
Artisan::call('migrate', ['--force' => true]);
if (DB::table('migrations')->count() !== $count || DB::table('groq_demo_budgets')->where('id', 1)->count() !== 1) {
    throw new RuntimeException('Remigration did not restore current schema/budget seed.');
}
echo 'REMIGRATE_EMPTY_QA PASS ('.$count." migrations)\n";
