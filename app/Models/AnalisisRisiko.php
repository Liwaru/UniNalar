<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalisisRisiko extends Model
{
    protected $table = 'analisis_risiko';

    protected $fillable = ['mahasiswa_id', 'semester_id', 'dianalisis_oleh_user_id', 'skor', 'kategori', 'versi_model', 'ringkasan', 'dianalisis_pada'];

    protected function casts(): array
    {
        return ['skor' => 'decimal:4', 'ringkasan' => 'array', 'dianalisis_pada' => 'datetime'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function dianalisisOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dianalisis_oleh_user_id');
    }

    public function faktorRisiko(): HasMany
    {
        return $this->hasMany(FaktorRisiko::class);
    }

    public function rekomendasiIntervensi(): HasMany
    {
        return $this->hasMany(RekomendasiIntervensi::class);
    }
}
