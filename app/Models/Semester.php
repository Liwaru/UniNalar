<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $table = 'semester';

    protected $fillable = ['kode', 'tahun_ajaran', 'periode', 'tanggal_mulai', 'tanggal_selesai', 'krs_mulai_pada', 'krs_selesai_pada', 'aktif'];

    protected function casts(): array
    {
        return ['periode' => 'integer', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'aktif' => 'boolean'];
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class);
    }

    public function krs(): HasMany
    {
        return $this->hasMany(Krs::class);
    }

    public function analisisRisiko(): HasMany
    {
        return $this->hasMany(AnalisisRisiko::class);
    }
}
