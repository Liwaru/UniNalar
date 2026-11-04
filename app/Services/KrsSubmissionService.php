<?php

namespace App\Services;

use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\KrsPengajuanRiwayat;
use App\Models\Mahasiswa;
use App\Models\Semester;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class KrsSubmissionService
{
    public function workflowAvailable(): bool
    {
        if (! $this->hasRequiredColumns([
            'users' => ['id', 'name'],
            'program_studi' => ['id', 'kode', 'aktif'],
            'dosen' => ['id', 'user_id', 'nama', 'aktif'],
            'mahasiswa' => ['id', 'user_id', 'nim', 'nama', 'program_studi_id'],
            'semester' => ['id', 'kode', 'aktif', 'krs_mulai_pada', 'krs_selesai_pada'],
            'mata_kuliah' => ['id', 'program_studi_id', 'kode', 'nama', 'sks'],
            'kelas_kuliah' => ['id', 'mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas', 'kapasitas'],
            'krs' => [
                'id', 'mahasiswa_id', 'semester_id', 'status', 'diajukan_pada',
                'diputuskan_oleh_user_id', 'diputuskan_pada', 'catatan_keputusan',
            ],
            'krs_detail' => ['id', 'krs_id', 'kelas_kuliah_id', 'status'],
            'krs_pengajuan_riwayat' => [
                'id', 'krs_id', 'versi', 'diajukan_pada', 'diajukan_oleh_user_id', 'status',
                'diputuskan_oleh_user_id', 'diputuskan_pada', 'catatan_keputusan', 'snapshot_kelas',
                'created_at', 'updated_at',
            ],
        ])) {
            return false;
        }

        $indexes = [
            'krs' => Schema::getIndexes('krs'),
            'krs_pengajuan_riwayat' => Schema::getIndexes('krs_pengajuan_riwayat'),
        ];

        if (! $this->hasIndex($indexes['krs'], ['mahasiswa_id', 'semester_id'], true)
            || ! $this->hasIndex($indexes['krs_pengajuan_riwayat'], ['krs_id', 'versi'], true)
            || ! $this->hasIndex($indexes['krs_pengajuan_riwayat'], ['status', 'diajukan_pada'])) {
            return false;
        }

        if (! collect($indexes['krs'])->contains(fn (array $index): bool => $index['primary'] && $index['columns'] === ['id'])
            || ! collect($indexes['krs_pengajuan_riwayat'])->contains(fn (array $index): bool => $index['primary'] && $index['columns'] === ['id'])) {
            return false;
        }

        $foreignKeys = collect([
            'mahasiswa', 'mata_kuliah', 'kelas_kuliah', 'krs', 'krs_detail', 'krs_pengajuan_riwayat',
        ])->mapWithKeys(fn (string $table): array => [$table => Schema::getForeignKeys($table)]);

        foreach ([
            ['table' => 'mahasiswa', 'columns' => ['program_studi_id'], 'referenced_table' => 'program_studi', 'on_delete' => 'restrict'],
            ['table' => 'mahasiswa', 'columns' => ['user_id'], 'referenced_table' => 'users', 'on_delete' => 'set null'],
            ['table' => 'mata_kuliah', 'columns' => ['program_studi_id'], 'referenced_table' => 'program_studi', 'on_delete' => 'restrict'],
            ['table' => 'kelas_kuliah', 'columns' => ['mata_kuliah_id'], 'referenced_table' => 'mata_kuliah', 'on_delete' => 'restrict'],
            ['table' => 'kelas_kuliah', 'columns' => ['semester_id'], 'referenced_table' => 'semester', 'on_delete' => 'restrict'],
            ['table' => 'kelas_kuliah', 'columns' => ['dosen_id'], 'referenced_table' => 'dosen', 'on_delete' => 'set null'],
            ['table' => 'krs', 'columns' => ['mahasiswa_id'], 'referenced_table' => 'mahasiswa', 'on_delete' => 'restrict'],
            ['table' => 'krs', 'columns' => ['semester_id'], 'referenced_table' => 'semester', 'on_delete' => 'restrict'],
            ['table' => 'krs', 'columns' => ['diputuskan_oleh_user_id'], 'referenced_table' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'krs_detail', 'columns' => ['krs_id'], 'referenced_table' => 'krs', 'on_delete' => 'cascade'],
            ['table' => 'krs_detail', 'columns' => ['kelas_kuliah_id'], 'referenced_table' => 'kelas_kuliah', 'on_delete' => 'restrict'],
            ['table' => 'krs_pengajuan_riwayat', 'columns' => ['krs_id'], 'referenced_table' => 'krs', 'on_delete' => 'restrict'],
            ['table' => 'krs_pengajuan_riwayat', 'columns' => ['diajukan_oleh_user_id'], 'referenced_table' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'krs_pengajuan_riwayat', 'columns' => ['diputuskan_oleh_user_id'], 'referenced_table' => 'users', 'on_delete' => 'restrict'],
        ] as $foreignKey) {
            if (! $this->hasForeignKey(
                $foreignKeys[$foreignKey['table']],
                $foreignKey['columns'],
                $foreignKey['referenced_table'],
                $foreignKey['on_delete'],
            )) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, list<string>> $requirements */
    private function hasRequiredColumns(array $requirements): bool
    {
        foreach ($requirements as $table => $columns) {
            if (! Schema::hasTable($table)) {
                return false;
            }

            if (array_diff($columns, Schema::getColumnListing($table)) !== []) {
                return false;
            }
        }

        return true;
    }

    /** @param list<string> $columns */
    private function hasIndex(array $indexes, array $columns, bool $unique = false): bool
    {
        return collect($indexes)->contains(
            fn (array $index): bool => $index['columns'] === $columns && (! $unique || $index['unique']),
        );
    }

    /** @param list<string> $columns */
    private function hasForeignKey(array $foreignKeys, array $columns, string $referencedTable, string $onDelete): bool
    {
        return collect($foreignKeys)->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === $columns
            && $foreignKey['foreign_table'] === $referencedTable
            && $foreignKey['foreign_columns'] === ['id']
            && strtolower($foreignKey['on_delete']) === $onDelete
        );
    }

    public function registrationIsOpen(Semester $semester): bool
    {
        if (! $this->workflowAvailable()) {
            return false;
        }

        $start = $semester->getRawOriginal('krs_mulai_pada');
        $end = $semester->getRawOriginal('krs_selesai_pada');
        if (! $start || ! $end) {
            return false;
        }

        $now = CarbonImmutable::now('Asia/Jakarta');

        return $now->greaterThanOrEqualTo(CarbonImmutable::parse($start, 'Asia/Jakarta'))
            && $now->lessThanOrEqualTo(CarbonImmutable::parse($end, 'Asia/Jakarta'));
    }

    public function availableClasses(Mahasiswa $mahasiswa, Semester $semester): Builder
    {
        return KelasKuliah::query()
            ->with(['mataKuliah', 'dosen'])
            ->where('semester_id', $semester->id)
            ->whereHas('mataKuliah', fn (Builder $course) => $course
                ->where('program_studi_id', $mahasiswa->program_studi_id)
                ->where('kode', 'not like', 'DEMO%')
                ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO')->where('aktif', true)))
            ->whereHas('semester', fn (Builder $query) => $query->where('kode', 'not like', 'DEMO%'))
            ->orderBy('mata_kuliah_id')->orderBy('kode_kelas');
    }

    public function addClass(User $user, int $classId): KrsDetail
    {
        return DB::transaction(function () use ($user, $classId): KrsDetail {
            $mahasiswa = $this->lockStudentProfile($user);
            $semester = $this->currentSemester();
            $this->assertWindowOpen($semester);
            $krs = $this->lockOrCreateKrs($mahasiswa, $semester);
            $this->assertEditable($krs);

            $class = $this->availableClasses($mahasiswa, $semester)->whereKey($classId)->lockForUpdate()->first();
            if (! $class || ! $class->mataKuliah) {
                throw ValidationException::withMessages(['kelas_kuliah_id' => 'Kelas tidak tersedia untuk program studi dan semester aktif Anda.']);
            }

            if ($krs->details()->where('kelas_kuliah_id', $class->id)->exists()) {
                throw ValidationException::withMessages(['kelas_kuliah_id' => 'Kelas tersebut sudah tercatat pada KRS.']);
            }

            if ($krs->details()->whereHas('kelasKuliah', fn (Builder $query) => $query->where('mata_kuliah_id', $class->mata_kuliah_id))->exists()) {
                throw ValidationException::withMessages(['kelas_kuliah_id' => 'Mata kuliah yang sama tidak dapat diambil pada dua kelas.']);
            }

            $this->assertCreditLimit($krs, (int) $class->mataKuliah->sks);
            $this->assertCapacity($class, $krs);

            return $krs->details()->create(['kelas_kuliah_id' => $class->id, 'status' => 'diambil']);
        }, 3);
    }

    public function removeClass(User $user, int $detailId): void
    {
        DB::transaction(function () use ($user, $detailId): void {
            $mahasiswa = $this->lockStudentProfile($user);
            $semester = $this->currentSemester();
            $this->assertWindowOpen($semester);
            $krs = $this->lockOrCreateKrs($mahasiswa, $semester);
            $this->assertEditable($krs);
            $detail = $krs->details()->lockForUpdate()->findOrFail($detailId);
            $detail->delete();
        }, 3);
    }

    public function submit(User $user): KrsPengajuanRiwayat
    {
        return DB::transaction(function () use ($user): KrsPengajuanRiwayat {
            $mahasiswa = $this->lockStudentProfile($user);
            $semester = $this->currentSemester();
            $this->assertWindowOpen($semester);
            $krs = $this->lockOrCreateKrs($mahasiswa, $semester);
            $this->assertEditable($krs);

            $details = $krs->details()->with('kelasKuliah.mataKuliah')->orderBy('id')->lockForUpdate()->get();
            if ($details->isEmpty()) {
                throw ValidationException::withMessages(['krs' => 'Pilih setidaknya satu kelas sebelum mengajukan KRS.']);
            }

            $classIds = $details->pluck('kelas_kuliah_id')->unique()->sort()->values();
            $classes = $this->availableClasses($mahasiswa, $semester)->whereIn('id', $classIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($classes->count() !== $classIds->count()) {
                throw ValidationException::withMessages(['krs' => 'Salah satu kelas tidak lagi tersedia pada semester aktif. Periksa kembali pilihan Anda.']);
            }

            $courseIds = $classes->pluck('mata_kuliah_id');
            if ($courseIds->unique()->count() !== $courseIds->count()) {
                throw ValidationException::withMessages(['krs' => 'KRS tidak boleh memuat mata kuliah yang sama pada lebih dari satu kelas.']);
            }

            $totalSks = $classes->sum(fn (KelasKuliah $class) => (int) $class->mataKuliah->sks);
            if ($totalSks > (int) config('academic.krs.maximum_sks', 24)) {
                throw ValidationException::withMessages(['krs' => 'Jumlah SKS melebihi batas pengajuan yang dikonfigurasi.']);
            }

            foreach ($classes as $class) {
                $this->assertCapacity($class, $krs);
            }

            $version = (int) $krs->riwayatPengajuan()->lockForUpdate()->max('versi') + 1;
            $snapshot = ['kelas' => $classes->map(fn (KelasKuliah $class): array => [
                'kelas_kuliah_id' => (int) $class->id,
                'mata_kuliah_id' => (int) $class->mata_kuliah_id,
                'kode_mata_kuliah' => (string) $class->mataKuliah->kode,
                'nama_mata_kuliah' => (string) $class->mataKuliah->nama,
                'kode_kelas' => (string) $class->kode_kelas,
                'sks' => (int) $class->mataKuliah->sks,
            ])->values()->all()];
            $submittedAt = now('UTC');

            $history = $krs->riwayatPengajuan()->create([
                'versi' => $version,
                'diajukan_pada' => $submittedAt,
                'diajukan_oleh_user_id' => $user->id,
                'status' => 'menunggu',
                'snapshot_kelas' => $snapshot,
            ]);

            $krs->forceFill([
                'status' => 'menunggu',
                'diajukan_pada' => $submittedAt,
                'diputuskan_oleh_user_id' => null,
                'diputuskan_pada' => null,
                'catatan_keputusan' => null,
            ])->save();

            return $history;
        }, 3);
    }

    private function lockStudentProfile(User $user): Mahasiswa
    {
        abort_unless((int) $user->level === User::ROLE_MAHASISWA, 403);
        abort_unless($this->workflowAvailable(), 503, 'Alur pengajuan KRS belum tersedia sampai schema pendukung diterapkan.');
        $mahasiswa = Mahasiswa::query()->with('programStudi')->where('user_id', $user->id)->lockForUpdate()->first();
        abort_unless($mahasiswa, 404, 'Profil mahasiswa tidak ditemukan.');
        abort_if(str_starts_with(strtoupper($mahasiswa->nim), 'DEMO') || str_ends_with(strtoupper($mahasiswa->programStudi?->kode ?? ''), '-DEMO'), 403, 'Akun demo tidak dapat mengubah data operasional.');

        return $mahasiswa;
    }

    private function currentSemester(): Semester
    {
        $semester = Semester::query()->where('aktif', true)->where('kode', 'not like', 'DEMO%')->orderByDesc('id')->lockForUpdate()->first();
        abort_unless($semester, 404, 'Semester operasional aktif belum tersedia.');

        return $semester;
    }

    private function assertWindowOpen(Semester $semester): void
    {
        if (! $this->registrationIsOpen($semester)) {
            throw ValidationException::withMessages(['krs' => 'Periode pengajuan KRS belum dibuka, sudah ditutup, atau belum diatur.']);
        }
    }

    private function lockOrCreateKrs(Mahasiswa $mahasiswa, Semester $semester): Krs
    {
        // The locked student row serializes first-time KRS creation for the unique student/semester pair.
        $krs = Krs::query()->where('mahasiswa_id', $mahasiswa->id)->where('semester_id', $semester->id)->lockForUpdate()->first();

        if ($krs) {
            return $krs;
        }

        $created = Krs::query()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'semester_id' => $semester->id,
            'status' => 'draft',
        ]);

        return Krs::query()->lockForUpdate()->findOrFail($created->id);
    }

    private function assertEditable(Krs $krs): void
    {
        if (! in_array($krs->status, ['draft', 'ditolak'], true)) {
            throw ValidationException::withMessages(['krs' => 'KRS hanya dapat diubah ketika berstatus draft atau ditolak.']);
        }
    }

    private function assertCreditLimit(Krs $krs, int $additionalSks): void
    {
        $current = $krs->details()->with('kelasKuliah.mataKuliah')->get()->sum(fn (KrsDetail $detail) => (int) ($detail->kelasKuliah?->mataKuliah?->sks ?? 0));
        if ($current + $additionalSks > (int) config('academic.krs.maximum_sks', 24)) {
            throw ValidationException::withMessages(['kelas_kuliah_id' => 'Jumlah SKS akan melebihi batas pengajuan yang dikonfigurasi.']);
        }
    }

    private function assertCapacity(KelasKuliah $class, Krs $currentKrs): void
    {
        if ($class->kapasitas === null) {
            return;
        }

        $reserved = KrsDetail::query()->where('kelas_kuliah_id', $class->id)
            ->whereHas('krs', fn (Builder $query) => $query->whereIn('status', ['menunggu', 'disetujui'])->where('id', '<>', $currentKrs->id))
            ->count();
        if ($reserved >= (int) $class->kapasitas) {
            throw ValidationException::withMessages(['kelas_kuliah_id' => 'Kapasitas kelas telah terpenuhi.']);
        }
    }
}
