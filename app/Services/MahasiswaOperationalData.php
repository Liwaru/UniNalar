<?php

namespace App\Services;

use App\Models\AnalisisRisiko;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/** Shared operational-data rules for student pages. */
class MahasiswaOperationalData
{
    public function context(User $user): array
    {
        $profileTableAvailable = Schema::hasTable('mahasiswa');
        $profile = $profileTableAvailable
            ? Mahasiswa::query()
                ->when(Schema::hasTable('program_studi'), fn ($query) => $query->with('programStudi'))
                ->where('user_id', $user->id)
                ->first()
            : null;

        if ($profile && ! Schema::hasTable('program_studi')) {
            $profile->setRelation('programStudi', null);
        }

        $isDemoProfile = $profile !== null && (
            str_starts_with(strtoupper($profile->nim), 'DEMO')
            || str_ends_with(strtoupper($profile->programStudi?->kode ?? ''), '-DEMO')
            || str_ends_with(strtolower((string) $user->email), '@demo.uninlar.test')
            || str_ends_with(strtolower((string) $profile->email), '@demo.uninlar.test')
        );

        if ($isDemoProfile) {
            $profile = null;
        }

        return [
            'mahasiswa' => $profile,
            'isDemoProfile' => $isDemoProfile,
            'tabelMahasiswaTersedia' => $profileTableAvailable,
            'skemaAkademikTersedia' => collect([
                'program_studi', 'mahasiswa', 'semester', 'mata_kuliah', 'kelas_kuliah', 'krs', 'krs_detail',
                'nilai', 'kehadiran', 'analisis_risiko', 'faktor_risiko', 'rekomendasi_intervensi', 'tindak_lanjut',
            ])->every(fn (string $table) => Schema::hasTable($table)),
        ];
    }

    public function hasTables(array $tables): bool
    {
        return collect($tables)->every(fn (string $table) => Schema::hasTable($table));
    }

    public function activeSemester()
    {
        return Schema::hasTable('semester')
            ? Semester::query()
                ->where('aktif', true)
                ->where('kode', 'not like', 'DEMO%')
                ->orderByDesc('tanggal_mulai')
                ->orderByDesc('id')
                ->first()
            : null;
    }

    public function krsQuery(int $mahasiswaId): Builder
    {
        return $this->scopeKrs(Krs::query(), $mahasiswaId);
    }

    public function scopeKrs(Builder $query, int $mahasiswaId): Builder
    {
        return OperationalAcademicData::scopeKrs($query)->where('mahasiswa_id', $mahasiswaId);
    }

    public function analysisQuery(int $mahasiswaId): Builder
    {
        return $this->scopeAnalysis(AnalisisRisiko::query(), $mahasiswaId);
    }

    public function scopeAnalysis(Builder $query, int $mahasiswaId): Builder
    {
        return OperationalAcademicData::scopeAnalysis($query)->where('mahasiswa_id', $mahasiswaId);
    }
}
