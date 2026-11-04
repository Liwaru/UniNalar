<?php

namespace App\Services;

use App\Models\Krs;
use App\Models\KrsPengajuanRiwayat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KrsDecisionService
{
    public function decide(int $historyId, User $actor, string $decision, ?string $note): KrsPengajuanRiwayat
    {
        abort_unless((int) $actor->level === User::ROLE_ADMIN_AKADEMIK, 403);
        abort_unless(in_array($decision, ['disetujui', 'ditolak'], true), 404);

        return DB::transaction(function () use ($historyId, $actor, $decision, $note): KrsPengajuanRiwayat {
            $history = KrsPengajuanRiwayat::query()->findOrFail($historyId);
            $krs = Krs::query()->lockForUpdate()->findOrFail($history->krs_id);
            $history = KrsPengajuanRiwayat::query()->whereKey($historyId)->lockForUpdate()->firstOrFail();
            $latest = KrsPengajuanRiwayat::query()->where('krs_id', $krs->id)->orderByDesc('versi')->lockForUpdate()->first();

            abort_unless(OperationalAcademicData::krs()->whereKey($krs->id)->exists(), 404);
            if ($krs->status !== 'menunggu' || $history->status !== 'menunggu' || $latest?->id !== $history->id) {
                throw ValidationException::withMessages(['krs' => 'Pengajuan ini sudah diputuskan atau bukan versi yang sedang menunggu.']);
            }

            $decidedAt = now('UTC');
            $history->forceFill([
                'status' => $decision,
                'diputuskan_oleh_user_id' => $actor->id,
                'diputuskan_pada' => $decidedAt,
                'catatan_keputusan' => $note,
            ])->save();
            $krs->forceFill([
                'status' => $decision,
                'diputuskan_oleh_user_id' => $actor->id,
                'diputuskan_pada' => $decidedAt,
                'catatan_keputusan' => $note,
            ])->save();

            return $history;
        }, 3);
    }
}
