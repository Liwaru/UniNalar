<?php

use Illuminate\Support\Facades\DB;
use Tests\Support\IsolatedMysql;

$app = require __DIR__.'/mysql-bootstrap.php';
DB::purge();
$start = hrtime(true);
$identity = IsolatedMysql::verify($app);
$connected = hrtime(true);
$samples = [];
foreach (range(1, 5) as $i) {
    $queryStart = hrtime(true);
    DB::selectOne('SELECT 1');
    $samples[] = (hrtime(true) - $queryStart) / 1e6;
}
echo json_encode(['identity' => $identity, 'connect_and_identity_ms' => ($connected - $start) / 1e6, 'select_ms' => $samples]).PHP_EOL;
