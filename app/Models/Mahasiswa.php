<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';

    protected $fillable = ['user_id', 'nim', 'nama', 'email', 'no_telepon', 'angkatan', 'program_studi_id', 'status'];

    protected function casts(): array
    {
        return ['angkatan' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function krs(): HasMany
    {
        return $this->hasMany(Krs::class);
    }

    public function analisisRisiko(): HasMany
    {
        return $this->hasMany(AnalisisRisiko::class);
    }
}
