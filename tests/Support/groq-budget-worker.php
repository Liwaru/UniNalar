<?php

// Standalone process for isolated SQLite concurrency QA; never accepts a production database.
use App\Services\GroqDemoBudget;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;

$root = getenv('APP_BASE_PATH');
$database = $argv[1] ?? '';
if (getenv('APP_ENV') !== 'testing' || ! is_string($root) || ! str_starts_with($root, '/tmp/uninalar-')
    || is_file($root.'/.env') || ! preg_match('#^/tmp/groq-budget-[a-f0-9]{16}\.sqlite$#D', $database)
    || ! is_file($database) || is_link($database)) {
    throw new RuntimeException('Refusing nonisolated budget worker.');
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
    'database.connections.sqlite.url' => null, 'services.groq.demo.global_limit' => 1,
    'services.groq.demo.user_limit' => 1]);
while (microtime(true) < (float) $argv[3]) {
    usleep(1000);
}
try {
    $token = app(GroqDemoBudget::class)->reserve((int) $argv[2]);
    usleep(300000);
    app(GroqDemoBudget::class)->release($token);
    echo 'reserved';
} catch (DomainException|QueryException) {
    echo 'blocked';
}
