<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class KrsPengajuanRiwayat extends Model
{
    protected $table = 'krs_pengajuan_riwayat';

    protected $fillable = [
        'krs_id', 'versi', 'diajukan_pada', 'diajukan_oleh_user_id', 'status',
        'diputuskan_oleh_user_id', 'diputuskan_pada', 'catatan_keputusan', 'snapshot_kelas',
    ];

    protected function casts(): array
    {
        return [
            'versi' => 'integer',
            'diajukan_pada' => 'datetime',
            'diputuskan_pada' => 'datetime',
            'snapshot_kelas' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $history): void {
            if ($history->isDirty(['krs_id', 'versi', 'diajukan_pada', 'diajukan_oleh_user_id', 'snapshot_kelas'])) {
                throw ValidationException::withMessages(['riwayat' => 'Identitas pengajuan dan snapshot riwayat tidak dapat diubah.']);
            }
        });

        static::deleting(function (): never {
            throw ValidationException::withMessages(['riwayat' => 'Riwayat pengajuan tidak dapat dihapus.']);
        });
    }

    public function krs(): BelongsTo
    {
        return $this->belongsTo(Krs::class);
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh_user_id');
    }

    public function diputuskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh_user_id');
    }
}
