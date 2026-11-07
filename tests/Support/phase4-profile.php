<?php

// Disposable source copies only: no operational environment or database is loaded.
use Illuminate\Contracts\Console\Kernel;

if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite'
    || getenv('DB_DATABASE') !== ':memory:' || is_file(dirname(__DIR__, 2).'/.env')
    || ! str_starts_with(dirname(__DIR__, 2), '/qa')) {
    throw new RuntimeException('Phase 4 profiling requires a secret-free isolated /qa source copy.');
}

$start = hrtime(true);
require dirname(__DIR__, 2).'/vendor/autoload.php';
$autoload = hrtime(true);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application = hrtime(true);
$phases = [];
$starts = [];
foreach (['LoadEnvironmentVariables', 'LoadConfiguration', 'HandleExceptions', 'RegisterFacades', 'SetRequestForConsole', 'RegisterProviders', 'BootProviders'] as $phase) {
    $class = 'Illuminate\\Foundation\\Bootstrap\\'.$phase;
    $app->beforeBootstrapping($class, function () use (&$starts, $phase) {
        $starts[$phase] = hrtime(true);
    });
    $app->afterBootstrapping($class, function () use (&$phases, &$starts, $phase) {
        $phases[$phase] = (hrtime(true) - $starts[$phase]) / 1e6;
    });
}
$app->make(Kernel::class)->bootstrap();
$boot = hrtime(true);
$app['view']->make('landing.index')->render();
$rendered = hrtime(true);
$files = array_slice(get_included_files(), 0, 150);
$ioStart = hrtime(true);
foreach ($files as $file) {
    file_get_contents($file);
}
$ioEnd = hrtime(true);
echo json_encode([
    'php' => PHP_VERSION,
    'autoload_ms' => ($autoload - $start) / 1e6,
    'application_ms' => ($application - $autoload) / 1e6,
    'bootstrap_ms' => ($boot - $application) / 1e6,
    'blade_ms' => ($rendered - $boot) / 1e6,
    'total_ms' => ($rendered - $start) / 1e6,
    'bootstrap_phases_ms' => $phases,
    'loaded_files' => count(get_included_files()),
    'io_files' => count($files),
    'io_read_ms' => ($ioEnd - $ioStart) / 1e6,
    'opcache_cli' => ini_get('opcache.enable_cli'),
    'config_cached' => $app->configurationIsCached(),
]).PHP_EOL;
