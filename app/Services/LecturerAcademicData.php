<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class LecturerAcademicData
{
    public function available(): bool
    {
        foreach ([
            'users' => ['id', 'email', 'level'],
            'dosen' => ['id', 'user_id', 'nama', 'nidn', 'email', 'program_studi_id', 'aktif'],
            'mahasiswa' => ['id', 'user_id', 'nim', 'nama', 'email', 'program_studi_id'],
            'program_studi' => ['id', 'kode'],
            'semester' => ['id', 'kode'],
            'mata_kuliah' => ['id', 'program_studi_id', 'kode', 'nama', 'sks'],
            'kelas_kuliah' => ['id', 'mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas'],
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
}
