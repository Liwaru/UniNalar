<?php

use Illuminate\Contracts\Console\Kernel;
use Tests\Support\IsolatedMysql;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
IsolatedMysql::verify($app);

return $app;
