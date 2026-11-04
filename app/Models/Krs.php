<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Krs extends Model
{
    protected $table = 'krs';

    protected $fillable = [
        'mahasiswa_id', 'semester_id', 'status', 'diajukan_pada',
        'diputuskan_oleh_user_id', 'diputuskan_pada', 'catatan_keputusan',
    ];

    protected function casts(): array
    {
        return ['diajukan_pada' => 'datetime', 'diputuskan_pada' => 'datetime'];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(KrsDetail::class);
    }

    public function riwayatPengajuan(): HasMany
    {
        return $this->hasMany(KrsPengajuanRiwayat::class);
    }
}
