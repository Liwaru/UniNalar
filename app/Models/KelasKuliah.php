<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasKuliah extends Model
{
    protected $table = 'kelas_kuliah';

    protected $fillable = ['mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas', 'kapasitas', 'ruang'];

    protected function casts(): array
    {
        return ['kapasitas' => 'integer'];
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function krsDetails(): HasMany
    {
        return $this->hasMany(KrsDetail::class);
    }
}
