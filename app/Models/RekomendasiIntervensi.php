<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekomendasiIntervensi extends Model
{
    protected $table = 'rekomendasi_intervensi';

    protected $fillable = ['analisis_risiko_id', 'faktor_risiko_id', 'judul', 'deskripsi', 'prioritas', 'status', 'tenggat_pada'];

    protected function casts(): array
    {
        return ['tenggat_pada' => 'date'];
    }

    public function analisisRisiko(): BelongsTo
    {
        return $this->belongsTo(AnalisisRisiko::class);
    }

    public function faktorRisiko(): BelongsTo
    {
        return $this->belongsTo(FaktorRisiko::class);
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(TindakLanjut::class);
    }
}
