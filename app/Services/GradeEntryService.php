<?php

namespace App\Services;

use App\Models\Nilai;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradeEntryService
{
    public function __construct(private readonly GradeFinalizationService $finalization, private readonly LecturerAcademicData $data) {}

    public function save(User $actor, int $detailId, array $values): Nilai
    {
        abort_unless((int) $actor->level === User::ROLE_DOSEN, 403);
        abort_unless($this->finalization->workflowAvailable(), 503, 'Pengelolaan nilai belum tersedia sampai schema pendukung diterapkan.');
        $values = Validator::make($values, [
            'nilai_angka' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'nilai_huruf' => ['required', Rule::in(array_keys(config('academic.grade_points', [])))],
            'revision' => ['nullable', 'string', 'size:64'],
        ])->validate();
        $lecturer = $this->data->profile($actor);
        abort_unless($lecturer, 404);

        return DB::transaction(function () use ($lecturer, $detailId, $values): Nilai {
            // Serializes inserts for a detail; existing-grade lock also serializes finalization.
            $detail = $this->data->details($lecturer)->lockForUpdate()->findOrFail($detailId);
            $grade = Nilai::query()->where('krs_detail_id', $detail->id)->lockForUpdate()->first();
            if ($grade?->final_at !== null) {
                throw ValidationException::withMessages(['nilai' => 'Nilai yang telah difinalisasi tidak dapat diubah.']);
            }
            if (! hash_equals(self::revision($grade), (string) ($values['revision'] ?? ''))) {
                throw ValidationException::withMessages(['nilai' => 'Nilai telah berubah sejak halaman dibuka. Muat ulang sebelum menyimpan kembali.']);
            }
            $points = config('academic.grade_points', []);
            $letter = $values['nilai_huruf'];
            if (! array_key_exists($letter, $points)) {
                throw ValidationException::withMessages(['nilai_huruf' => 'Pilih huruf dari skala akademik yang tersedia.']);
            }
            $grade ??= new Nilai(['krs_detail_id' => $detail->id]);
            $grade->fill([
                'nilai_angka' => $values['nilai_angka'],
                'nilai_huruf' => $letter,
                'nilai_mutu' => $points[$letter],
            ])->save();

            return $grade;
        }, 3);
    }

    public static function revision(?Nilai $grade): string
    {
        return $grade ? hash('sha256', json_encode($grade->only([
            'id', 'krs_detail_id', 'nilai_angka', 'nilai_huruf', 'nilai_mutu', 'final_at', 'updated_at',
        ]), JSON_THROW_ON_ERROR)) : '';
    }
}
