<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaktorRisiko extends Model
{
    protected $table = 'faktor_risiko';

    protected $fillable = ['analisis_risiko_id', 'kode_faktor', 'nama', 'nilai_faktor', 'bobot', 'kontribusi', 'penjelasan'];

    protected function casts(): array
    {
        return ['nilai_faktor' => 'decimal:4', 'bobot' => 'decimal:4', 'kontribusi' => 'decimal:4'];
    }

    public function analisisRisiko(): BelongsTo
    {
        return $this->belongsTo(AnalisisRisiko::class);
    }

    public function rekomendasiIntervensi(): HasMany
    {
        return $this->hasMany(RekomendasiIntervensi::class);
    }
}
