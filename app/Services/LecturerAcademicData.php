<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LecturerAcademicData
{
    public function available(): bool
    {
        foreach ([
            'users' => ['id', 'email', 'level'],
            'dosen' => ['id', 'user_id', 'nama', 'nidn', 'email', 'program_studi_id', 'aktif'],
            'mahasiswa' => ['id', 'user_id', 'nim', 'nama', 'email', 'program_studi_id'],
            'program_studi' => ['id', 'kode', 'nama'],
            'semester' => ['id', 'kode', 'tahun_ajaran', 'periode'],
            'mata_kuliah' => ['id', 'program_studi_id', 'kode', 'nama', 'sks'],
            'kelas_kuliah' => ['id', 'mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas', 'kapasitas'],
            'krs' => ['id', 'mahasiswa_id', 'semester_id', 'status'],
            'krs_detail' => ['id', 'krs_id', 'kelas_kuliah_id', 'status'],
        ] as $table => $columns) {
            // MySQL and SQLite return an empty column list for an absent table.
            if (array_diff($columns, Schema::getColumnListing($table)) !== []) {
                return false;
            }
        }

        return true;
    }

    public function profile(User $user): ?Dosen
    {
        return $this->available()
            ? OperationalAcademicData::lecturers()->where('user_id', $user->id)->where('aktif', true)->first()
            : null;
    }

    public function classes(Dosen $lecturer): Builder
    {
        return OperationalAcademicData::classes()->where('dosen_id', $lecturer->id);
    }

    public function details(Dosen $lecturer): Builder
    {
        return OperationalAcademicData::approvedDetails()
            ->whereHas('kelasKuliah', fn (Builder $q) => $q->where('dosen_id', $lecturer->id));
    }

    /** Filter choices are restricted to the lecturer's own operational classes. */
    public function filters(Request $request, ?Dosen $lecturer): array
    {
        $semesters = $lecturer ? Semester::query()->whereIn('id', $this->classes($lecturer)->select('semester_id'))
            ->orderByDesc('tahun_ajaran')->orderByDesc('periode')->get() : collect();
        $filters = $request->validate([
            'semester_id' => ['nullable', 'integer', Rule::in($semesters->pluck('id')->all())],
            'q' => ['nullable', 'string', 'max:255'],
            'status_nilai' => ['nullable', Rule::in(['belum_dinilai', 'draft', 'final'])],
        ]);
        $filters['q'] = trim($filters['q'] ?? '');
        $classOptions = $lecturer ? $this->classes($lecturer)
            ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id))
            ->with(['mataKuliah', 'semester'])->orderByDesc('semester_id')->orderBy('id')->get() : collect();
        $filters += $request->validate(['kelas_id' => ['nullable', 'integer', Rule::in($classOptions->pluck('id')->all())]]);

        return compact('semesters', 'filters', 'classOptions');
    }

    public function filterParticipants(Builder $query, array $filters, bool $gradeAvailable): Builder
    {
        $query->when($filters['q'] ?? '', fn ($q, $search) => $q->whereHas('krs.mahasiswa', fn ($s) => $s
            ->where(fn ($student) => $student->where('nama', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))));
        if ($gradeAvailable) {
            match ($filters['status_nilai'] ?? null) {
                'belum_dinilai' => $query->whereDoesntHave('nilai'),
                'draft' => $query->whereHas('nilai', fn ($q) => $q->whereNull('final_at')),
                'final' => $query->whereHas('nilai', fn ($q) => $q->whereNotNull('final_at')),
                default => null,
            };
        } elseif (! empty($filters['status_nilai'])) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
