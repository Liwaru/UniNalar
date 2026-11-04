<?php

namespace App\Services;

use App\Models\AnalisisRisiko;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Builder;

/** Explicit, shared filters for existing demo markers; never changes stored data. */
class OperationalAcademicData
{
    public static function students(): Builder
    {
        return self::scopeStudent(Mahasiswa::query());
    }

    public static function scopeStudent(Builder $query): Builder
    {
        return $query->where('nim', 'not like', 'DEMO%')
            ->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', 'not like', '%@demo.uninlar.test'))
            ->whereHas('programStudi', fn (Builder $q) => $q->where('kode', 'not like', '%-DEMO'))
            ->whereDoesntHave('user', fn (Builder $q) => $q->where('email', 'like', '%@demo.uninlar.test'));
    }

    public static function lecturers(): Builder
    {
        return self::scopeLecturer(Dosen::query());
    }

    public static function scopeLecturer(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('nidn')->orWhere('nidn', 'not like', 'DEMO%'))
            ->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', 'not like', '%@demo.uninlar.test'))
            ->whereDoesntHave('programStudi', fn (Builder $q) => $q->where('kode', 'like', '%-DEMO'))
            ->whereDoesntHave('user', fn (Builder $q) => $q->where('email', 'like', '%@demo.uninlar.test'));
    }

    public static function courses(): Builder
    {
        return self::scopeCourse(MataKuliah::query());
    }

    public static function scopeCourse(Builder $query): Builder
    {
        return $query->where('kode', 'not like', 'DEMO%')
            ->whereHas('programStudi', fn (Builder $q) => $q->where('kode', 'not like', '%-DEMO'));
    }

    public static function classes(): Builder
    {
        return self::scopeClass(KelasKuliah::query());
    }

    public static function scopeClass(Builder $query): Builder
    {
        return $query->where('kode_kelas', 'not like', 'DEMO%')
            ->whereHas('mataKuliah', fn (Builder $q) => self::scopeCourse($q))
            ->whereHas('semester', fn (Builder $q) => $q->where('kode', 'not like', 'DEMO%'))
            ->where(fn (Builder $q) => $q->whereNull('dosen_id')->orWhereHas('dosen', fn (Builder $d) => self::scopeLecturer($d)));
    }

    public static function krs(): Builder
    {
        return self::scopeKrs(Krs::query());
    }

    public static function scopeKrs(Builder $query): Builder
    {
        return $query->whereHas('mahasiswa', fn (Builder $q) => self::scopeStudent($q))
            ->whereHas('semester', fn (Builder $q) => $q->where('kode', 'not like', 'DEMO%'))
            ->whereDoesntHave('details', fn (Builder $q) => $q->whereDoesntHave('kelasKuliah',
                fn (Builder $c) => self::scopeClass($c)->whereColumn('kelas_kuliah.semester_id', 'krs.semester_id')));
    }

    public static function approvedDetails(): Builder
    {
        return KrsDetail::query()->where('status', 'diambil')
            ->whereHas('krs', fn (Builder $q) => self::scopeKrs($q)->where('status', 'disetujui'))
            ->whereHas('kelasKuliah', fn (Builder $q) => self::scopeClass($q));
    }

    public static function analyses(): Builder
    {
        return self::scopeAnalysis(AnalisisRisiko::query());
    }

    public static function scopeAnalysis(Builder $query): Builder
    {
        return $query->whereHas('mahasiswa', fn (Builder $q) => self::scopeStudent($q))
            ->where(fn (Builder $q) => $q->whereNull('semester_id')
                ->orWhereHas('semester', fn (Builder $s) => $s->where('kode', 'not like', 'DEMO%')))
            ->where(fn (Builder $q) => $q->whereNull('versi_model')->orWhere('versi_model', 'not like', 'demo%'))
            ->where(fn (Builder $q) => $q->whereNull('ringkasan->sumber')
                ->orWhereNotIn('ringkasan->sumber', ['data_dummy', 'demo', 'simulasi']));
    }
}
