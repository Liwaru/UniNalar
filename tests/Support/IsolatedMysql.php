<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Refuses writes until the actual temporary server and its external manifest agree. */
final class IsolatedMysql
{
    public static function verify(Application $app): array
    {
        $path = getenv('QA_MYSQL_MANIFEST');
        $manifest = $path && is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : null;
        if (! is_array($manifest) || ! $app->environment('testing') || config('database.default') !== 'mysql'
            || ! str_starts_with(base_path(), '/qa') || is_file(base_path('.env'))
            || ($manifest['run'] ?? '') !== getenv('QA_RUN_ID')
            || ($manifest['php_host'] ?? '') !== gethostname()
            || ($manifest['db_host'] ?? '') !== config('database.connections.mysql.host')
            || ($manifest['database'] ?? '') !== config('database.connections.mysql.database')
            || ! str_starts_with($manifest['database'] ?? '', 'qa_')
            || config('database.connections.mysql.username') !== 'qa_runner'
            || ($manifest['server_uuid'] ?? '') === ($manifest['operational_uuid'] ?? '')
            || empty($manifest['network_internal']) || empty($manifest['mysql_tmpfs'])) {
            throw new RuntimeException('Refusing MySQL QA: environment/manifest isolation mismatch.');
        }

        $db = DB::connection();
        $actual = (array) $db->selectOne('SELECT DATABASE() AS db, @@server_uuid AS uuid, VERSION() AS version');
        if ($actual['db'] !== $manifest['database'] || $actual['uuid'] !== $manifest['server_uuid']
            || ! str_starts_with($actual['version'], '8.4.')) {
            throw new RuntimeException('Refusing MySQL QA: actual server identity mismatch.');
        }
        $marker = $db->table('qa_isolation_guard')->where('run_id', $manifest['run'])->value('token_sha');
        if (! is_string($marker) || ! hash_equals((string) getenv('QA_MYSQL_MARKER'), $marker)) {
            throw new RuntimeException('Refusing MySQL QA: server-side isolation marker mismatch.');
        }

        return $actual;
    }
}
