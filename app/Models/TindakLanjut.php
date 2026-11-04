<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjut extends Model
{
    protected $table = 'tindak_lanjut';

    protected $fillable = ['rekomendasi_intervensi_id', 'dilakukan_oleh_user_id', 'dilakukan_pada', 'status', 'catatan'];

    protected function casts(): array
    {
        return ['dilakukan_pada' => 'datetime'];
    }

    public function rekomendasiIntervensi(): BelongsTo
    {
        return $this->belongsTo(RekomendasiIntervensi::class);
    }

    public function dilakukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh_user_id');
    }
}
