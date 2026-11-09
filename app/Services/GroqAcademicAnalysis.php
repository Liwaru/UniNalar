<?php

namespace App\Services;

use App\Models\AnalisisRisiko;
use App\Models\Kehadiran;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use DomainException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class GroqAcademicAnalysis
{
    private const METHOD = 'descriptive-v2';

    public function __construct(private readonly GroqAcademicClient $client) {}

    public function indicators(Mahasiswa $student, int $semester): array
    {
        $details = OperationalAcademicData::approvedDetails()->whereHas('krs', fn ($q) => $q
            ->where('mahasiswa_id', $student->id)->where('semester_id', $semester));
        $grades = Nilai::whereNotNull('final_at')->whereIn('krs_detail_id', (clone $details)->select('krs_detail.id'))
            ->get(['nilai_angka']);
        $attendance = Kehadiran::whereIn('krs_detail_id', (clone $details)->select('krs_detail.id'))
            ->get(['status'])->countBy(fn ($row) => mb_strtolower(trim($row->status)));
        // Only canonical numeric aggregates leave the application, never free text.
        $counts = [];
        foreach (config('academic.attendance.status_aliases', []) as $status => $aliases) {
            $counts[$status] = collect($aliases)->sum(fn ($alias) => $attendance[$alias] ?? 0);
        }

        $summary = app(AcademicGradeSummary::class)->forStudents(collect([$student]), $semester)->get($student->id);
        $credits = (clone $details)->with('kelasKuliah.mataKuliah')->get()
            ->sum(fn ($detail) => (int) $detail->kelasKuliah->mataKuliah->sks);

        return ['mata_kuliah_disetujui' => $details->count(), 'jumlah_nilai_final' => $grades->count(),
            'rata_rata_nilai_final' => $grades->whereNotNull('nilai_angka')->isEmpty() ? null : round($grades->avg('nilai_angka'), 2),
            'kehadiran_tercatat' => $counts, 'sks_disetujui' => $credits,
            'ips_semester' => $summary['ips'], 'ipk_kumulatif' => $summary['ipk']];
    }

    public function generate(Mahasiswa $student, int $semester): AnalisisRisiko
    {
        $policy = app(GroqLivePolicy::class);
        $policy->assertAccess($student);
        try {
            return Cache::lock('groq-academic:'.$student->id.':'.$semester, 90)->block(1, function () use ($student, $semester, $policy) {
                $data = $this->indicators($student, $semester);
                $fingerprint = hash('sha256', json_encode([$data, config('services.groq.model'), self::METHOD], JSON_THROW_ON_ERROR));
                $existing = AnalisisRisiko::where('mahasiswa_id', $student->id)->where('semester_id', $semester)
                    ->where('ringkasan->fingerprint', $fingerprint)->where('ringkasan->sumber', 'groq_deskriptif')
                    ->where('ringkasan->transport', $policy->transport())
                    ->latest('id')->first();
                if ($existing) {
                    return $existing;
                }
                if ($data['mata_kuliah_disetujui'] === 0) {
                    throw new DomainException('Belum ada mata kuliah disetujui untuk semester ini.');
                }
                $policy->assertProvider();
                $limit = 'groq-academic-user:'.$student->user_id;
                if (RateLimiter::tooManyAttempts($limit, 2)) {
                    throw new DomainException('Batas permintaan tercapai. Tunggu satu menit sebelum mencoba kembali.');
                }
                RateLimiter::hit($limit, 60);
                $budget = app(GroqDemoBudget::class);
                $lease = $policy->productionDemo() ? $budget->reserve((int) $student->user_id) : null;
                try {
                    // A previous request may have completed between lookup and reservation.
                    if ($lease !== null && ($completed = AnalisisRisiko::where('mahasiswa_id', $student->id)
                        ->where('semester_id', $semester)->where('ringkasan->fingerprint', $fingerprint)
                        ->where('ringkasan->sumber', 'groq_deskriptif')->where('ringkasan->transport', 'live')
                        ->latest('id')->first())) {
                        return $completed;
                    }

                    return $this->describeAndSave($student, $semester, $fingerprint, $data, $lease);
                } finally {
                    if ($lease !== null) {
                        try {
                            $budget->release($lease);
                        } catch (\Throwable) {
                            // Fail closed until the lease expires; never hide the original outcome.
                        }
                    }
                }
            });
        } catch (LockTimeoutException) {
            throw new DomainException('Analisis sedang diproses. Periksa kembali hasil beberapa saat lagi.');
        }
    }

    private function describeAndSave(Mahasiswa $student, int $semester, string $fingerprint, array $data, ?string $lease): AnalisisRisiko
    {
        $result = $this->client->describe($data);
        if ($data !== $this->indicators($student, $semester)) {
            throw new DomainException('Data akademik berubah selama analisis. Silakan coba kembali.');
        }

        try {
            return DB::transaction(function () use ($student, $semester, $fingerprint, $data, $result, $lease) {
                if ($lease !== null) {
                    app(GroqDemoBudget::class)->assertLease($lease);
                }
                $analysis = AnalisisRisiko::create(['mahasiswa_id' => $student->id, 'semester_id' => $semester,
                    'dianalisis_oleh_user_id' => $student->user_id, 'skor' => null, 'kategori' => null,
                    'versi_model' => config('services.groq.model'), 'dianalisis_pada' => now(),
                    'ringkasan' => ['sumber' => 'groq_deskriptif', 'fingerprint' => $fingerprint,
                        'metode' => self::METHOD, 'indikator' => $data, 'penjelasan' => $result['penjelasan'],
                        'usage' => $result['usage'], 'transport' => $result['transport'], 'klasifikasi' => 'NEEDS SPECIFICATION']]);
                foreach ($result['rekomendasi'] as $text) {
                    $analysis->rekomendasiIntervensi()->create(['judul' => $text, 'deskripsi' => $text]);
                }

                return $analysis;
            });
        } catch (QueryException) {
            throw new DomainException('Respons AI diterima tetapi hasil gagal disimpan. Tidak ada hasil baru yang tersimpan. Periksa riwayat sebelum mencoba lagi; permintaan tidak diulang otomatis.');
        }
    }
}
