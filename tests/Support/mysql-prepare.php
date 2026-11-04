<?php

use Illuminate\Support\Facades\Artisan;
use Tests\Support\IsolatedMysql;

$app = require __DIR__.'/mysql-bootstrap.php';
echo json_encode(IsolatedMysql::verify($app)).PHP_EOL;
Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
