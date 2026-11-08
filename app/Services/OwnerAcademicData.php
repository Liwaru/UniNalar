<?php

namespace App\Services;

use App\Models\Kehadiran;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\TindakLanjut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/** Read-only monitoring. Level 4 has institution-wide scope in the existing model. */
class OwnerAcademicData
{
    public function available(): bool
    {
        return app(AdminAcademicData::class)->available();
    }

    public function has(string ...$tables): bool
    {
        return collect($tables)->every(fn ($table) => Schema::hasTable($table));
    }

    public function finalizationAvailable(): bool
    {
        return $this->has('nilai') && Schema::hasColumn('nilai', 'final_at');
    }

    public function context(Request $request, bool $search = true, bool $classFilter = false, array $statuses = []): array
    {
        $available = $this->available();
        $programs = $available ? app(AdminAcademicData::class)->masterQuery('program-studi')->orderBy('nama')->get(['id', 'nama']) : collect();
        $semesters = $available ? app(AdminAcademicData::class)->masterQuery('semester')->orderByDesc('tahun_ajaran')->orderByDesc('periode')->get(['id', 'kode']) : collect();
        $rules = [
            'program_studi_id' => ['nullable', 'integer', Rule::in($programs->pluck('id')->all())],
            'semester_id' => ['nullable', 'integer', Rule::in($semesters->pluck('id')->all())],
        ];
        if ($search) {
            $rules['q'] = ['nullable', 'string', 'max:255'];
        }
        $validatedScope = $request->validate($rules, AdminAcademicData::messages());
        $classes = collect();
        if ($classFilter) {
            $classes = $available ? $this->classes(['semester_id' => $validatedScope['semester_id'] ?? null])->when($validatedScope['program_studi_id'] ?? null, fn ($q) => $q->whereIn('kelas_kuliah.id', $this->details($validatedScope)->select('kelas_kuliah_id')))->with(['mataKuliah', 'semester'])->orderBy('kode_kelas')->get(['id', 'kode_kelas', 'mata_kuliah_id', 'semester_id']) : collect();
            $rules['kelas_kuliah_id'] = ['nullable', 'integer', Rule::in($classes->pluck('id')->all())];
        }
        if ($statuses) {
            $rules['status'] = ['nullable', 'string', Rule::in($statuses)];
        }
        $filters = $request->validate($rules, AdminAcademicData::messages(), ['q' => 'Pencarian', 'program_studi_id' => 'Program studi', 'semester_id' => 'Semester', 'kelas_kuliah_id' => 'Kelas', 'status' => 'Status']);
        if ($search) {
            $filters['q'] = trim($filters['q'] ?? '');
        }

        return compact('available', 'programs', 'semesters', 'classes', 'filters', 'search', 'classFilter', 'statuses');
    }

    public function students(array $f): Builder
    {
        return OperationalAcademicData::students()
            ->when($f['program_studi_id'] ?? null, fn ($q, $id) => $q->where('program_studi_id', $id))
            ->when($f['semester_id'] ?? null, fn ($q, $id) => $q->whereHas('krs', fn ($k) => OperationalAcademicData::scopeKrs($k)->where('semester_id', $id)));
    }

    public function searchStudent(Builder $q, string $search): Builder
    {
        return $q->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('nama', 'like', '%'.$search.'%')->orWhere('nim', 'like', '%'.$search.'%')));
    }

    public function classes(array $f): Builder
    {
        return OperationalAcademicData::classes()
            ->when($f['program_studi_id'] ?? null, fn ($q, $id) => $q->whereHas('mataKuliah', fn ($c) => $c->where('program_studi_id', $id)))
            ->when($f['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id))
            ->when($f['kelas_kuliah_id'] ?? null, fn ($q, $id) => $q->whereKey($id));
    }

    public function lecturers(array $f): Builder
    {
        return OperationalAcademicData::lecturers()
            ->when($f['program_studi_id'] ?? null, fn ($q, $id) => $q->where('program_studi_id', $id))
            ->when($f['semester_id'] ?? null, fn ($q) => $q->whereHas('kelasKuliah', fn ($c) => $c->whereIn('kelas_kuliah.id', $this->classes(['semester_id' => $f['semester_id']])->select('kelas_kuliah.id'))));
    }

    public function krs(array $f): Builder
    {
        return OperationalAcademicData::krs()
            ->whereIn('mahasiswa_id', $this->students(['program_studi_id' => $f['program_studi_id'] ?? null])->select('mahasiswa.id'))
            ->when($f['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id));
    }

    public function details(array $f): Builder
    {
        return OperationalAcademicData::approvedDetails()->whereIn('krs_id', $this->krs($f)->select('krs.id'))
            ->when($f['kelas_kuliah_id'] ?? null, fn ($q, $id) => $q->where('kelas_kuliah_id', $id));
    }

    public function grades(array $f): Builder
    {
        return Nilai::query()->whereIn('krs_detail_id', $this->details($f)->select('krs_detail.id'))
            ->when($this->finalizationAvailable(), fn ($q) => $q->whereNotNull('final_at'), fn ($q) => $q->whereRaw('1 = 0'));
    }

    public function attendance(array $f): Builder
    {
        return Kehadiran::query()->whereIn('krs_detail_id', $this->details($f)->select('krs_detail.id'));
    }

    public function analyses(array $f): Builder
    {
        return OperationalAcademicData::analyses()->whereIn('mahasiswa_id', $this->students(['program_studi_id' => $f['program_studi_id'] ?? null])->select('mahasiswa.id'))
            ->when($f['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id));
    }

    public function recommendations(array $f): Builder
    {
        return RekomendasiIntervensi::query()->whereIn('analisis_risiko_id', $this->analyses($f)->select('analisis_risiko.id'));
    }

    public function followUps(array $f): Builder
    {
        return TindakLanjut::query()->whereIn('rekomendasi_intervensi_id', $this->recommendations($f)->select('rekomendasi_intervensi.id'));
    }

    public function classCounts(Builder $query, array $f): Builder
    {
        $query->withCount(['krsDetails as official_count' => fn ($q) => $q->whereIn('krs_detail.id', $this->details(['semester_id' => $f['semester_id'] ?? null])->select('krs_detail.id'))]);
        if ($this->finalizationAvailable()) {
            $query->withCount(['krsDetails as final_count' => fn ($q) => $q->whereIn('krs_detail.id', $this->details(['semester_id' => $f['semester_id'] ?? null])->select('krs_detail.id'))->whereHas('nilai', fn ($n) => $n->whereNotNull('final_at'))]);
        }

        return $query;
    }
}
