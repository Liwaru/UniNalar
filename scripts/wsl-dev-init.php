<?php

use App\Services\WslDevelopmentIdentity;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$m = WslDevelopmentIdentity::verify();
if ($m['initialized'] || DB::selectOne('SELECT COUNT(*) AS n FROM sqlite_master')->n !== 0) {
    throw new RuntimeException('Initialization requires the verified brand-new empty SQLite.');
}
Artisan::call('migrate', ['--force' => true]);
$m['initialized'] = true;
file_put_contents(storage_path('app/private/wsl-dev-runtime.json'), json_encode($m, JSON_PRETTY_PRINT));
echo "Verified isolated empty SQLite migrated; no seeder executed.\n";
