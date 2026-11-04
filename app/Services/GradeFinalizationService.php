<?php

namespace App\Services;

use App\Models\Nilai;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GradeFinalizationService
{
    public function workflowAvailable(): bool
    {
        if (! app(LecturerAcademicData::class)->available()) {
            return false;
        }

        if (array_diff(
            ['id', 'krs_detail_id', 'nilai_angka', 'nilai_huruf', 'nilai_mutu', 'final_at', 'difinalkan_oleh_user_id'],
            Schema::getColumnListing('nilai'),
        ) !== []) {
            return false;
        }

        $indexes = Schema::getIndexes('nilai');
        $hasFinalizedAtIndex = collect($indexes)->contains(
            fn (array $index): bool => $index['columns'] === ['final_at'],
        );
        $foreignKeys = Schema::getForeignKeys('nilai');
        $hasRestrictedUserForeignKey = collect($foreignKeys)->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['difinalkan_oleh_user_id']
            && $foreignKey['foreign_table'] === 'users'
            && $foreignKey['foreign_columns'] === ['id']
            && strtolower($foreignKey['on_delete']) === 'restrict'
        );
        $hasDetailForeignKey = collect($foreignKeys)->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['krs_detail_id']
            && $foreignKey['foreign_table'] === 'krs_detail'
            && $foreignKey['foreign_columns'] === ['id']
            && strtolower($foreignKey['on_delete']) === 'cascade'
        );

        $hasUniqueDetail = collect($indexes)->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['krs_detail_id']);

        return $hasFinalizedAtIndex && $hasRestrictedUserForeignKey && $hasDetailForeignKey && $hasUniqueDetail;
    }

    public function finalize(User $user, int $gradeId): Nilai
    {
        abort_unless((int) $user->level === User::ROLE_DOSEN, 403);
        abort_unless($this->workflowAvailable(), 503, 'Finalisasi nilai belum tersedia sampai schema pendukung diterapkan.');
        $data = app(LecturerAcademicData::class);
        $lecturer = $data->profile($user);
        abort_unless($lecturer, 404);

        return DB::transaction(function () use ($user, $lecturer, $gradeId, $data): Nilai {
            $grade = Nilai::query()->whereKey($gradeId)
                ->whereIn('krs_detail_id', $data->details($lecturer)->select('krs_detail.id'))
                ->lockForUpdate()->firstOrFail();

            if ($grade->final_at !== null) {
                throw ValidationException::withMessages(['nilai' => 'Nilai ini sudah difinalisasi dan tidak dapat difinalisasi ulang.']);
            }

            $letter = strtoupper(trim((string) $grade->nilai_huruf));
            if ($grade->nilai_angka !== null && ((float) $grade->nilai_angka < 0 || (float) $grade->nilai_angka > 100)) {
                throw ValidationException::withMessages(['nilai' => 'Nilai angka berada di luar rentang 0–100.']);
            }
            $gradePoints = config('academic.grade_points', []);
            if ($letter === '' || ! array_key_exists($letter, $gradePoints)) {
                throw ValidationException::withMessages(['nilai' => 'Nilai huruf belum tercatat atau tidak terdapat pada skala nilai yang dikonfigurasi.']);
            }

            if ($grade->nilai_mutu !== null && abs((float) $grade->nilai_mutu - (float) $gradePoints[$letter]) > 0.01) {
                throw ValidationException::withMessages(['nilai' => 'Nilai mutu tidak sesuai dengan skala nilai huruf yang dikonfigurasi. Perbaiki catatan nilai melalui alur yang berwenang sebelum finalisasi.']);
            }

            $grade->forceFill(['final_at' => now('UTC'), 'difinalkan_oleh_user_id' => $user->id])->save();

            return $grade->refresh();
        }, 3);
    }
}
