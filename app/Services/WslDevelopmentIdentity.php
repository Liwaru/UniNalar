<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WslDevelopmentIdentity
{
    public static function verify(): array
    {
        $file = storage_path('app/private/wsl-dev-runtime.json');
        $m = is_file($file) ? json_decode(file_get_contents($file), true) : null;
        if (! is_array($m) || ($m['root'] ?? null) !== base_path()
            || ! preg_match('/^[a-f0-9]{16}$/', $m['tag'] ?? '')
            || ($m['database'] ?? null) !== database_path('wsl-dev-'.$m['tag'].'.sqlite')
            || ! app()->environment('local') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.url')
            || config('database.connections.sqlite.database') !== $m['database']
            || ! is_file($m['database']) || is_link($m['database'])) {
            throw new RuntimeException('Development database identity rejected.');
        }
        $connection = DB::connection();
        $identity = $connection->select('PRAGMA database_list');
        if ($connection->getDriverName() !== 'sqlite'
            || realpath($identity[0]->file ?? '') !== realpath($m['database'])) {
            throw new RuntimeException('Actual SQLite identity rejected.');
        }

        return $m;
    }
}
