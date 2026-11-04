<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Nilai extends Model
{
    protected $table = 'nilai';

    protected $fillable = ['krs_detail_id', 'nilai_angka', 'nilai_huruf', 'nilai_mutu', 'final_at', 'difinalkan_oleh_user_id'];

    protected function casts(): array
    {
        return ['nilai_angka' => 'decimal:2', 'nilai_mutu' => 'decimal:2', 'final_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $nilai): void {
            if ($nilai->getOriginal('final_at') !== null) {
                throw ValidationException::withMessages(['nilai' => 'Nilai yang telah difinalisasi tidak dapat diubah.']);
            }
        });

        static::deleting(function (self $nilai): void {
            if ($nilai->final_at !== null) {
                throw ValidationException::withMessages(['nilai' => 'Nilai yang telah difinalisasi tidak dapat dihapus.']);
            }
        });
    }

    public function krsDetail(): BelongsTo
    {
        return $this->belongsTo(KrsDetail::class);
    }

    public function difinalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difinalkan_oleh_user_id');
    }
}
