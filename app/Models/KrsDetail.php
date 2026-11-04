<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KrsDetail extends Model
{
    protected $table = 'krs_detail';

    protected $fillable = ['krs_id', 'kelas_kuliah_id', 'status'];

    public function krs(): BelongsTo
    {
        return $this->belongsTo(Krs::class);
    }

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class);
    }

    public function nilai(): HasOne
    {
        return $this->hasOne(Nilai::class);
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class);
    }
}
