<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Explicit local demo provisioning; never invoked by production/default seeders. */
final class DevelopmentDemoAccounts
{
    public const SYNTHETIC_NIDN = 'SIM-JOVIAN-001';

    public function create(array $academic, array $owned = []): array
    {
        if (app()->environment('testing')) {
            if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
                throw new RuntimeException('Demo QA requires SQLite memory isolation.');
            }
        } else {
            WslDevelopmentIdentity::verify();
        }

        return DB::transaction(function () use ($academic, $owned) {
            $student = Mahasiswa::findOrFail($academic['student']);
            $teacher = Dosen::findOrFail($academic['lecturer']);
            $definitions = [
                'mahasiswa' => ['legacy' => 'hendrik', 'name' => 'Hendrik', 'level' => 1, 'user' => $student->user],
                'dosen' => ['legacy' => 'jovian', 'name' => 'Jovian', 'level' => 2, 'user' => $teacher->user],
                'admin' => ['legacy' => 'luiz', 'name' => 'Luiz', 'level' => 3],
                'pimpinan' => ['legacy' => 'liwaru', 'name' => 'Liwaru', 'level' => 4],
            ];
            if ($student->nim !== '24161033' || $student->nama !== 'Hendrik' || $teacher->nama !== 'Jovian') {
                throw new RuntimeException('Academic fixture identity mismatch; demo provisioning rejected.');
            }
            $existing = [];
            // Preflight every account before any write; legacy registry IDs are preserved.
            foreach ($definitions as $username => $definition) {
                $id = $owned[$username] ?? $owned[$definition['legacy']] ?? null;
                $user = $definition['user'] ?? ($id ? User::findOrFail($id) : null);
                if ($user && ($user->level !== $definition['level'] || $user->name !== $definition['name']
                    || ! in_array($user->username, [$username, $definition['legacy']], true)
                    || ($id && $user->id !== $id))) {
                    throw new RuntimeException('Owned demo identity changed; refusing to overwrite another account.');
                }
                if (! $user && $definition['level'] <= 2) {
                    throw new RuntimeException('Academic demo account must already be linked.');
                }
                $conflict = User::get(['id', 'username'])->contains(fn ($candidate) => in_array(UsernameNormalization::canonical($candidate->username), [$username, $definition['legacy']], true)
                    && $candidate->id !== $user?->id);
                if ($conflict) {
                    throw new RuntimeException('Demo username is already used by an unowned account; review required.');
                }
                $existing[$username] = $user;
            }
            if (! in_array($teacher->nidn, [null, self::SYNTHETIC_NIDN], true)
                || Dosen::where('nidn', self::SYNTHETIC_NIDN)->where('id', '!=', $teacher->id)->exists()) {
                throw new RuntimeException('Synthetic lecturer identity conflict; provisioning rejected.');
            }
            foreach ($definitions as $username => $definition) {
                $user = $existing[$username] ?? User::create([
                    'name' => $definition['name'], 'level' => $definition['level'],
                    'username' => $username, 'email' => $username.'@fixtures.invalid', 'password' => $username,
                ]);
                $credentialsChanged = $user->username !== $username || ! Hash::check($username, $user->password);
                if ($credentialsChanged) {
                    $user->forceFill(['username' => $username, 'password' => $username, 'remember_token' => null])->save();
                }
                $existing[$username] = $user;
            }
            if ($teacher->nidn !== self::SYNTHETIC_NIDN) {
                $teacher->forceFill(['nidn' => self::SYNTHETIC_NIDN])->save();
            }

            return collect($existing)->map(fn ($user) => (int) $user->id)->all();
        });
    }
}
