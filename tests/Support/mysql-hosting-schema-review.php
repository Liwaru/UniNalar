<?php

use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/mysql-bootstrap.php';
echo json_encode([
    'server' => DB::selectOne('SELECT VERSION() AS version, @@server_uuid AS uuid, @@transaction_isolation AS isolation_level, @@collation_database AS db_collation, @@session.time_zone AS session_timezone, @@system_time_zone AS system_timezone, @@session.sql_mode AS sql_mode'),
    'tables' => DB::select('SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME'),
    'columns' => DB::select("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('analisis_risiko','rekomendasi_intervensi','groq_demo_budgets','groq_demo_user_budgets') ORDER BY TABLE_NAME,ORDINAL_POSITION"),
    'foreign_keys' => DB::select('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,ORDINAL_POSITION'),
    'indexes' => DB::select('SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX'),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
