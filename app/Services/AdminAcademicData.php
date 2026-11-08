<?php

namespace App\Services;

use App\Models\KrsPengajuanRiwayat;
use App\Models\ProgramStudi;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminAcademicData
{
    public static function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'required_with' => ':attribute wajib diisi bersama :values.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'boolean' => 'Pilihan :attribute tidak valid.',
            'email' => 'Format :attribute tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'exists' => 'Pilihan :attribute tidak tersedia.',
            'in' => 'Pilihan :attribute tidak valid.',
            'max.string' => ':attribute maksimal :max karakter.',
            'between.numeric' => ':attribute harus berada antara :min dan :max.',
            'date' => 'Format :attribute tidak valid.',
            'date_format' => 'Format :attribute tidak valid.',
            'after_or_equal' => ':attribute tidak boleh mendahului :date.',
            'regex' => 'Format :attribute tidak valid.',
            'not_regex' => ':attribute menggunakan penanda khusus data demo.',
        ];
    }

    public function available(): bool
    {
        return app(LecturerAcademicData::class)->available();
    }

    public function masterQuery(string $resource): Builder
    {
        return match ($resource) {
            'program-studi' => ProgramStudi::query()->where('kode', 'not like', '%-DEMO'),
            'semester' => Semester::query()->where('kode', 'not like', 'DEMO%'),
            'mata-kuliah' => OperationalAcademicData::courses(),
            'dosen' => OperationalAcademicData::lecturers(),
            'mahasiswa' => OperationalAcademicData::students(),
            'kelas-kuliah' => OperationalAcademicData::classes(),
        };
    }

    public function filters(Request $request, array $fields): array
    {
        $filterOptions = [];
        foreach ($fields as $field) {
            $filterOptions[$field] = match ($field) {
                'program_studi_id' => ['label' => 'Program studi', 'choices' => $this->masterQuery('program-studi')->orderBy('nama')->pluck('nama', 'id')->all()],
                'semester_id' => ['label' => 'Semester', 'choices' => $this->masterQuery('semester')->orderByDesc('id')->pluck('kode', 'id')->all()],
                'dosen_id' => ['label' => 'Dosen', 'choices' => $this->masterQuery('dosen')->orderBy('nama')->pluck('nama', 'id')->all()],
                'mata_kuliah_id' => ['label' => 'Mata kuliah', 'choices' => $this->masterQuery('mata-kuliah')->orderBy('kode')->pluck('kode', 'id')->all()],
                'aktif' => ['label' => 'Status', 'choices' => ['1' => 'Aktif', '0' => 'Nonaktif']],
                'status' => ['label' => 'Status mahasiswa', 'choices' => ['aktif' => 'Aktif', 'cuti' => 'Cuti', 'lulus' => 'Lulus', 'keluar' => 'Keluar']],
            };
        }
        $rules = ['q' => ['nullable', 'string', 'max:255']];
        foreach ($filterOptions as $field => $definition) {
            $rules[$field] = ['nullable', Rule::in(array_keys($definition['choices']))];
            if (str_ends_with($field, '_id')) {
                $rules[$field][] = 'integer';
            }
        }
        $attributes = ['q' => 'Pencarian'];
        foreach ($filterOptions as $field => $definition) {
            $attributes[$field] = $definition['label'];
        }
        $filters = $request->validate($rules, self::messages(), $attributes);
        $filters['q'] = trim($filters['q'] ?? '');

        return compact('filters', 'filterOptions');
    }

    public function apply(Builder $query, array $filters, bool $classQuery = false): Builder
    {
        foreach ($filters as $field => $value) {
            if ($field === 'q' || $value === null || $value === '') {
                continue;
            }
            if ($classQuery && $field === 'program_studi_id') {
                $query->whereHas('mataKuliah', fn ($q) => $q->where('program_studi_id', $value));
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    public function submissions(): Builder
    {
        return KrsPengajuanRiwayat::query()->select('krs_pengajuan_riwayat.*')
            ->addSelect(['latest_version' => DB::table('krs_pengajuan_riwayat as latest')->selectRaw('MAX(latest.versi)')->whereColumn('latest.krs_id', 'krs_pengajuan_riwayat.krs_id')])
            ->whereHas('krs', fn ($q) => OperationalAcademicData::scopeKrs($q));
    }
}
