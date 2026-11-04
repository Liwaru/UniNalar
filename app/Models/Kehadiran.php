<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kehadiran extends Model
{
    protected $table = 'kehadiran';

    protected $fillable = ['krs_detail_id', 'dicatat_oleh_dosen_id', 'pertemuan_ke', 'tanggal', 'status', 'catatan'];

    protected function casts(): array
    {
        return ['pertemuan_ke' => 'integer', 'tanggal' => 'date'];
    }

    public function krsDetail(): BelongsTo
    {
        return $this->belongsTo(KrsDetail::class);
    }

    public function dicatatOlehDosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dicatat_oleh_dosen_id');
    }
}
