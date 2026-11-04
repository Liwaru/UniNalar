<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    protected $table = 'dosen';

    protected $fillable = ['user_id', 'nidn', 'nama', 'email', 'no_telepon', 'program_studi_id', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class);
    }

    public function kehadiranDicatat(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'dicatat_oleh_dosen_id');
    }
}
