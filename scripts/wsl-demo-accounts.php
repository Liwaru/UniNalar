<?php

use App\Services\DevelopmentDemoAccounts;
use App\Services\WslDevelopmentIdentity;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
WslDevelopmentIdentity::verify();
$lock = fopen(storage_path('app/private/wsl-demo-accounts.lock'), 'c');
if (! $lock || ! flock($lock, LOCK_EX)) {
    throw new RuntimeException('Unable to lock development demo provisioning.');
}
try {
    $m = WslDevelopmentIdentity::verify();
    if (! isset($m['academic_fixture'])) {
        throw new RuntimeException('Create the verified academic fixtures first.');
    }
    $m['demo_accounts'] = app(DevelopmentDemoAccounts::class)->create($m['academic_fixture'], $m['demo_accounts'] ?? []);
    $path = storage_path('app/private/wsl-dev-runtime.json');
    $temp = $path.'.tmp';
    file_put_contents($temp, json_encode($m, JSON_PRETTY_PRINT));
    rename($temp, $path);
    echo "Four local demo roles verified; credentials are not printed.\n";
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
