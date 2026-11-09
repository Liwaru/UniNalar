<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UsernameNormalization
{
    public static function canonical(?string $value): ?string
    {
        $value = mb_strtolower(trim($value ?? ''), 'UTF-8');

        return $value === '' ? null : $value;
    }

    /** Preflight every account before any write; never invent missing usernames. */
    public static function apply(): void
    {
        DB::transaction(function () {
            $rows = DB::table('users')->orderBy('id')->lockForUpdate()->get(['id', 'username']);
            $seen = [];
            foreach ($rows as $row) {
                $name = self::canonical($row->username);
                if ($name !== null && (mb_strlen($name) > 50 || isset($seen[$name]))) {
                    throw new RuntimeException('Username normalization collision/length conflict; review account IDs before migration.');
                }
                if ($name !== null) {
                    $seen[$name] = $row->id;
                }
            }
            foreach ($rows as $row) {
                $name = self::canonical($row->username);
                if ($name !== $row->username) {
                    DB::table('users')->where('id', $row->id)->update(['username' => $name]);
                }
            }
        });
    }
}
