<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    protected $table = 'mata_kuliah';

    protected $fillable = ['program_studi_id', 'kode', 'nama', 'sks', 'semester_rekomendasi'];

    protected function casts(): array
    {
        return ['sks' => 'integer', 'semester_rekomendasi' => 'integer'];
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class);
    }
}
