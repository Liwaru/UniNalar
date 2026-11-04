<?php

namespace App\Http\Controllers;

use App\Models\AnalisisRisiko;
use App\Models\Kehadiran;
use App\Models\Krs;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\TindakLanjut;
use App\Services\MahasiswaOperationalData;
use App\Services\AcademicGradeSummary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MahasiswaDashboardController extends Controller
{
    public function __construct(private readonly MahasiswaOperationalData $data, private readonly AcademicGradeSummary $gradeSummary)
    {
    }

    public function index(Request $request): View
    {
        $context = $this->data->context($request->user());
        $mahasiswa = $context['mahasiswa'];
        $semesterAktif = $this->data->activeSemester();
        $canLoadKrs = $this->data->hasTables(['krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester']);
        $relations = ['semester', 'details.kelasKuliah.mataKuliah'];

        if (Schema::hasTable('nilai')) {
            $relations[] = 'details.nilai';
        }

        if (Schema::hasTable('kehadiran')) {
            $relations[] = 'details.kehadiran';
        }

        $krsRecords = $mahasiswa && $canLoadKrs
            ? $this->data->krsQuery($mahasiswa->id)->with($relations)->get()
            : collect();
        $nilai = Schema::hasTable('nilai')
            ? $krsRecords->flatMap(fn (Krs $krs) => $krs->details)
                ->map(fn ($detail) => $detail->nilai?->nilai_angka)
                ->filter(fn ($value) => $value !== null)
                ->values()
            : collect();
        $kehadiran = Schema::hasTable('kehadiran')
            ? $krsRecords->flatMap(fn (Krs $krs) => $krs->details)->flatMap(fn ($detail) => $detail->kehadiran)
            : collect();
        $attendanceSummary = $this->attendanceSummary($kehadiran);
        $activeKrs = $semesterAktif
            ? $krsRecords->first(fn (Krs $krs) => (int) $krs->semester_id === (int) $semesterAktif->id)
            : null;
        $activeCourses = $activeKrs
            ? $activeKrs->details
                ->where('status', 'diambil')
                ->filter(fn ($detail) => $detail->kelasKuliah?->mataKuliah !== null)
                ->values()
            : collect();
        $latestAnalysis = $mahasiswa && Schema::hasTable('analisis_risiko')
            ? $this->data->analysisQuery($mahasiswa->id)
                ->orderByDesc('dianalisis_pada')->orderByDesc('id')->first()
            : null;
        $trend = $this->buildAcademicTrend($krsRecords);
        $gradeSummary = $mahasiswa ? $this->gradeSummary->forStudent($mahasiswa) : ['ips' => null, 'ips_semester' => null, 'ipk' => null, 'final_count' => 0];

        return view('dashboard.mahasiswa.index', $context + [
            'semesterAktif' => $semesterAktif,
            'rataRataNilai' => $nilai->isNotEmpty() ? $nilai->avg() : null,
            'jumlahNilai' => $nilai->count(),
            'jumlahCatatanKehadiran' => $kehadiran->count(),
            'attendanceSummary' => $attendanceSummary,
            'jumlahMataKuliahAktif' => $activeCourses->count(),
            'analisisTerbaru' => $latestAnalysis,
            'grafik' => $trend,
            'gradeSummary' => $gradeSummary,
        ]);
    }

    public function krs(Request $request): View
    {
        $context = $this->data->context($request->user());
        $krs = $context['mahasiswa'] && $this->data->hasTables(['krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? $this->data->krsQuery($context['mahasiswa']->id)
                ->with(['semester', 'details.kelasKuliah.mataKuliah'])
                ->orderByDesc('semester_id')->get()
            : collect();

        return $this->feature('krs', 'KRS dan Mata Kuliah', $context + ['krs' => $krs]);
    }

    public function nilai(Request $request): View
    {
        $context = $this->data->context($request->user());
        $values = $context['mahasiswa'] && $this->data->hasTables(['nilai', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? Nilai::query()
                ->whereHas('krsDetail.krs', fn (Builder $query) => $this->data->scopeKrs($query, $context['mahasiswa']->id))
                ->with(['krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])
                ->orderByDesc('id')->get()
            : collect();

        return $this->feature('nilai', 'Nilai Akademik', $context + [
            'nilai' => $values,
            'finalizationAvailable' => Schema::hasTable('nilai') && Schema::hasColumn('nilai', 'final_at'),
        ]);
    }

    public function kehadiran(Request $request): View
    {
        $context = $this->data->context($request->user());
        $records = $context['mahasiswa'] && $this->data->hasTables(['kehadiran', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? Kehadiran::query()
                ->whereHas('krsDetail.krs', fn (Builder $query) => $this->data->scopeKrs($query, $context['mahasiswa']->id))
                ->with(['krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])
                ->orderByDesc('tanggal')->orderByDesc('pertemuan_ke')->get()
            : collect();

        return $this->feature('kehadiran', 'Kehadiran', $context + ['kehadiran' => $records]);
    }

    public function analisisRisiko(Request $request): View
    {
        $context = $this->data->context($request->user());
        $analyses = $context['mahasiswa'] && $this->data->hasTables(['analisis_risiko', 'faktor_risiko', 'semester'])
            ? $this->data->analysisQuery($context['mahasiswa']->id)
                ->with(['semester', 'faktorRisiko' => fn ($query) => $query->orderByDesc('kontribusi')->orderBy('id')])
                ->orderByDesc('dianalisis_pada')->orderByDesc('id')->get()
            : collect();

        return $this->feature('analisis-risiko', 'Analisis Risiko', $context + ['analyses' => $analyses]);
    }

    public function rekomendasi(Request $request): View
    {
        $context = $this->data->context($request->user());
        $recommendations = $context['mahasiswa'] && $this->data->hasTables(['rekomendasi_intervensi', 'analisis_risiko', 'semester'])
            ? RekomendasiIntervensi::query()
                ->whereHas('analisisRisiko', fn (Builder $query) => $this->data->scopeAnalysis($query, $context['mahasiswa']->id))
                ->with(['analisisRisiko.semester', 'faktorRisiko'])
                ->orderByDesc('created_at')->orderByDesc('id')->get()
            : collect();

        return $this->feature('rekomendasi', 'Rekomendasi Intervensi', $context + ['recommendations' => $recommendations]);
    }

    public function tindakLanjut(Request $request): View
    {
        $context = $this->data->context($request->user());
        $followUps = $context['mahasiswa'] && $this->data->hasTables(['tindak_lanjut', 'rekomendasi_intervensi', 'analisis_risiko', 'semester'])
            ? TindakLanjut::query()
                ->whereHas('rekomendasiIntervensi.analisisRisiko', fn (Builder $query) => $this->data->scopeAnalysis($query, $context['mahasiswa']->id))
                ->with('rekomendasiIntervensi.analisisRisiko.semester')
                ->orderByDesc('dilakukan_pada')->orderByDesc('id')->get()
            : collect();

        return $this->feature('tindak-lanjut', 'Tindak Lanjut', $context + ['followUps' => $followUps]);
    }

    public function profil(Request $request): View
    {
        return $this->feature('profil', 'Profil Saya', $this->data->context($request->user()));
    }

    public function updateProfil(Request $request)
    {
        $mahasiswa = $this->data->context($request->user())['mahasiswa'];
        abort_unless($mahasiswa, 404);

        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('mahasiswa', 'email')->ignore($mahasiswa->id)],
            'no_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        $mahasiswa->fill($validated)->save();

        return back()->with('status', 'Informasi kontak berhasil diperbarui.');
    }

    private function feature(string $view, string $title, array $data): View
    {
        return view("dashboard.mahasiswa.{$view}", $data + ['pageTitle' => $title]);
    }

    private function buildAcademicTrend(Collection $krsRecords): array
    {
        $periods = $krsRecords
            ->filter(fn (Krs $krs) => $krs->semester !== null)
            ->sortBy(fn (Krs $krs) => sprintf('%s-%02d', $krs->semester->tahun_ajaran, $krs->semester->periode))
            ->map(function (Krs $krs): array {
                $scores = Schema::hasTable('nilai')
                    ? $krs->details->map(fn ($detail) => $detail->nilai?->nilai_angka)->filter(fn ($value) => $value !== null)
                    : collect();
                return [
                    'label' => $krs->semester->kode,
                    'nilai' => $scores->isNotEmpty() ? round((float) $scores->avg(), 2) : null,
                ];
            })->values();

        return [
            'labels' => $periods->pluck('label')->all(),
            'nilai' => $periods->pluck('nilai')->all(),
            'kehadiran' => $periods->pluck('kehadiran')->all(),
            'tersedia' => $periods->filter(fn (array $period) => $period['nilai'] !== null)->count() >= 2,
        ];
    }

    private function attendanceSummary(Collection $records): array
    {
        $aliases = config('academic.attendance.status_aliases', []);
        $counts = array_fill_keys(array_keys($aliases), 0);
        $unknown = 0;

        foreach ($records as $record) {
            $status = mb_strtolower(trim((string) $record->status));
            $canonical = collect($aliases)->search(fn (array $values) => in_array($status, $values, true));

            if ($canonical === false) {
                $unknown++;
                continue;
            }

            $counts[$canonical]++;
        }

        $validCount = array_sum($counts);
        $attendedCount = collect(config('academic.attendance.attended_statuses', []))
            ->sum(fn (string $status) => $counts[$status] ?? 0);
        $minimum = max(1, (int) config('academic.attendance.minimum_valid_records_for_percentage', 3));

        return [
            'counts' => $counts,
            'validCount' => $validCount,
            'unknownCount' => $unknown,
            'percentage' => $validCount >= $minimum ? round(($attendedCount / $validCount) * 100, 1) : null,
            'minimum' => $minimum,
        ];
    }
}
