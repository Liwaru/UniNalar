<?php

use App\Services\GradeEntryService;
use Illuminate\Support\Facades\DB;
use Tests\Support\MysqlFixtures;

$app = require __DIR__.'/mysql-bootstrap.php';
$data = MysqlFixtures::make();
$detail = MysqlFixtures::detail($data);
DB::enableQueryLog();
$start = hrtime(true);
app(GradeEntryService::class)->save($data['lecturerUser'], $detail->id, ['nilai_angka' => 80, 'nilai_huruf' => 'B']);
$queries = DB::getQueryLog();
echo json_encode(['queries' => count($queries), 'metadata' => count(array_filter($queries, fn ($q) => str_contains($q['query'], 'information_schema'))), 'total_ms' => (hrtime(true) - $start) / 1e6]).PHP_EOL;
