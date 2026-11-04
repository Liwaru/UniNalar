<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Nilai;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GradeFinalizationService
{
    public function workflowAvailable(): bool
    {
        if (! Schema::hasTable('nilai')) {
            return false;
        }

        if (array_diff(
            ['id', 'krs_detail_id', 'nilai_angka', 'nilai_huruf', 'nilai_mutu', 'final_at', 'difinalkan_oleh_user_id'],
            Schema::getColumnListing('nilai'),
        ) !== []) {
            return false;
        }

        $hasFinalizedAtIndex = collect(Schema::getIndexes('nilai'))->contains(
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

        return $hasFinalizedAtIndex && $hasRestrictedUserForeignKey && $hasDetailForeignKey;
    }

    public function finalize(User $user, int $gradeId): Nilai
    {
        abort_unless((int) $user->level === User::ROLE_DOSEN, 403);
        abort_unless($this->workflowAvailable(), 503, 'Finalisasi nilai belum tersedia sampai schema pendukung diterapkan.');
        $lecturer = Dosen::query()->where('user_id', $user->id)->where('aktif', true)->firstOrFail();

        return DB::transaction(function () use ($user, $lecturer, $gradeId): Nilai {
            $grade = Nilai::query()->whereKey($gradeId)
                ->whereHas('krsDetail.krs', fn (Builder $krs) => $krs->where('status', 'disetujui')
                    ->whereHas('mahasiswa', fn (Builder $student) => $student->where('nim', 'not like', 'DEMO%')
                        ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO'))))
                ->whereHas('krsDetail.kelasKuliah', fn (Builder $class) => $class->where('dosen_id', $lecturer->id)
                    ->whereHas('semester', fn (Builder $semester) => $semester->where('kode', 'not like', 'DEMO%'))
                    ->whereHas('mataKuliah', fn (Builder $course) => $course->where('kode', 'not like', 'DEMO%')
                        ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO'))))
                ->lockForUpdate()->firstOrFail();

            if ($grade->final_at !== null) {
                throw ValidationException::withMessages(['nilai' => 'Nilai ini sudah difinalisasi dan tidak dapat difinalisasi ulang.']);
            }

            $letter = strtoupper(trim((string) $grade->nilai_huruf));
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
