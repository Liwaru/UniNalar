<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$start = hrtime(true);
require dirname(__DIR__, 2).'/vendor/autoload.php';
$autoload = hrtime(true);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application = hrtime(true);
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$boot = hrtime(true);
$output = new BufferedOutput;
$kernel->handle(new ArrayInput(['--version' => true]), $output);
$artisan = hrtime(true);
$blade = hrtime(true);
$app['view']->make('landing.index')->render();
$rendered = hrtime(true);
echo json_encode([
    'autoload_ms' => ($autoload - $start) / 1e6,
    'application_ms' => ($application - $autoload) / 1e6,
    'bootstrap_ms' => ($boot - $application) / 1e6,
    'artisan_ms' => ($artisan - $boot) / 1e6,
    'blade_ms' => ($rendered - $blade) / 1e6,
    'total_ms' => ($rendered - $start) / 1e6,
]).PHP_EOL;
