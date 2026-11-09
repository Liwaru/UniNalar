<?php

namespace App\Http\Controllers;

use App\Models\AnalisisRisiko;
use App\Models\Kehadiran;
use App\Models\Krs;
use App\Models\Nilai;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Services\AcademicGradeSummary;
use App\Services\MahasiswaOperationalData;
use App\Services\OperationalAcademicData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MahasiswaDashboardController extends Controller
{
    public function __construct(private readonly MahasiswaOperationalData $data, private readonly AcademicGradeSummary $gradeSummary) {}

    public function index(Request $request): View
    {
        $context = $this->data->context($request->user());
        $mahasiswa = $context['mahasiswa'];
        $semesterAktif = $this->data->activeSemester();
        $canLoadKrs = $this->data->hasTables(['users', 'program_studi', 'dosen', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester']);
        $relations = ['semester', 'details.kelasKuliah.mataKuliah'];

        if (Schema::hasTable('nilai')) {
            $finalizationAvailable = Schema::hasColumn('nilai', 'final_at');
            // Draft grades remain visible with an explicit status on the grades page,
            // but must never contribute to published dashboard summaries or trends.
            $relations['details.nilai'] = fn ($query) => $finalizationAvailable
                ? $query->whereNotNull('final_at')
                : $query->whereRaw('1 = 0');
        }

        if (Schema::hasTable('kehadiran')) {
            $relations[] = 'details.kehadiran';
        }

        $krsRecords = $mahasiswa && $canLoadKrs
            ? $this->data->krsQuery($mahasiswa->id)->with($relations)->get()
            : collect();
        $nilai = Schema::hasTable('nilai')
            ? $krsRecords->where('status', 'disetujui')->flatMap(fn (Krs $krs) => $krs->details)
                ->where('status', 'diambil')
                ->map(fn ($detail) => $detail->nilai?->nilai_angka)
                ->filter(fn ($value) => $value !== null)
                ->values()
            : collect();
        $kehadiran = Schema::hasTable('kehadiran')
            ? $krsRecords->where('status', 'disetujui')->flatMap(fn (Krs $krs) => $krs->details)
                ->where('status', 'diambil')->flatMap(fn ($detail) => $detail->kehadiran)
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
        $latestAnalysis = $mahasiswa && $this->data->hasTables(['users', 'program_studi', 'analisis_risiko', 'semester'])
            ? $this->data->analysisQuery($mahasiswa->id)
                ->orderByDesc('dianalisis_pada')->orderByDesc('id')->first()
            : null;
        $trend = $this->buildAcademicTrend($krsRecords);
        $gradeSummary = $mahasiswa ? $this->gradeSummary->forStudent($mahasiswa) : ['ips' => null, 'ips_semester' => null, 'ipk' => null, 'final_count' => 0];
        $recommendationCount = $followUpCount = null;
        if ($mahasiswa && $this->data->hasTables(['users', 'program_studi', 'faktor_risiko', 'rekomendasi_intervensi', 'analisis_risiko', 'semester'])) {
            $recommendations = RekomendasiIntervensi::query()->whereHas('analisisRisiko', fn ($query) => $this->data->scopeAnalysis($query, $mahasiswa->id));
            $recommendationCount = (clone $recommendations)->count();
            if (Schema::hasTable('tindak_lanjut')) {
                $followUpCount = TindakLanjut::query()->whereIn('rekomendasi_intervensi_id', $recommendations->select('rekomendasi_intervensi.id'))->count();
            }
        }

        return view('dashboard.mahasiswa.index', $context + [
            'semesterAktif' => $semesterAktif,
            'rataRataNilai' => $nilai->isNotEmpty() ? $nilai->avg() : null,
            'jumlahNilai' => $nilai->count(),
            'jumlahCatatanKehadiran' => $kehadiran->count(),
            'attendanceSummary' => $attendanceSummary,
            'jumlahMataKuliahAktif' => $activeCourses->count(),
            'jumlahSksAktif' => $activeCourses->sum(fn ($detail) => (int) $detail->kelasKuliah->mataKuliah->sks),
            'activeKrs' => $activeKrs,
            'recommendationCount' => $recommendationCount,
            'followUpCount' => $followUpCount,
            'analisisTerbaru' => $latestAnalysis,
            'grafik' => $trend,
            'gradeSummary' => $gradeSummary,
        ]);
    }

    public function krs(Request $request): View
    {
        $context = $this->data->context($request->user());
        $krs = $context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'dosen', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? $this->data->krsQuery($context['mahasiswa']->id)
                ->with(['semester', 'details.kelasKuliah.mataKuliah'])
                ->orderByDesc('semester_id')->get()
            : collect();

        return $this->feature('krs', 'KRS dan Mata Kuliah', $context + ['krs' => $krs]);
    }

    public function nilai(Request $request): View
    {
        $context = $this->data->context($request->user());
        $filterData = $this->semesterFilters($request);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $values = $context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'dosen', 'nilai', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? Nilai::query()
                ->whereHas('krsDetail.krs', fn (Builder $query) => $this->data->scopeKrs($query, $context['mahasiswa']->id))
                ->when($semesterId, fn ($query, $id) => $query->whereHas('krsDetail.krs', fn ($semester) => $semester->where('semester_id', $id)))
                ->with(['krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])
                ->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return $this->feature('nilai', 'Nilai Akademik', $context + $filterData + [
            'nilai' => $values,
            'gradeSummary' => $context['mahasiswa'] ? $this->gradeSummary->forStudent($context['mahasiswa']) : ['ips' => null, 'ips_semester' => null, 'ipk' => null, 'final_count' => 0],
            'finalizationAvailable' => Schema::hasTable('nilai') && Schema::hasColumn('nilai', 'final_at'),
        ]);
    }

    public function kehadiran(Request $request): View
    {
        $context = $this->data->context($request->user());
        $filterData = $this->semesterFilters($request);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $query = $context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'dosen', 'kehadiran', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])
            ? Kehadiran::query()
                ->whereIn('krs_detail_id', OperationalAcademicData::approvedDetails()->select('krs_detail.id'))
                ->whereHas('krsDetail.krs', fn (Builder $query) => $this->data->scopeKrs($query, $context['mahasiswa']->id))
                ->when($semesterId, fn ($query, $id) => $query->whereHas('krsDetail.krs', fn ($semester) => $semester->where('semester_id', $id)))
                ->with(['krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])
                ->orderByDesc('tanggal')->orderByDesc('pertemuan_ke')->orderByDesc('id')
            : null;
        $summary = $this->attendanceSummary($query ? (clone $query)->withoutEagerLoads()->reorder()->get(['kehadiran.status']) : collect());
        $records = $query ? $query->paginate(25)->withQueryString() : collect();

        return $this->feature('kehadiran', 'Kehadiran', $context + $filterData + ['kehadiran' => $records, 'attendanceSummary' => $summary]);
    }

    public function analisisRisiko(Request $request): View
    {
        $context = $this->data->context($request->user());
        $filterData = $this->semesterFilters($request);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $analyses = $context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'analisis_risiko', 'faktor_risiko', 'semester'])
            ? $this->data->analysisQuery($context['mahasiswa']->id)
                ->when($semesterId, fn ($query, $id) => $query->where('semester_id', $id))
                ->with(['semester', 'rekomendasiIntervensi', 'faktorRisiko' => fn ($query) => $query->orderByDesc('kontribusi')->orderBy('id')])
                ->orderByDesc('dianalisis_pada')->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return $this->feature('analisis-risiko', 'Analisis Risiko', $context + $filterData + [
            'analyses' => $analyses,
            'academicHistory' => $context['mahasiswa'] ? $this->gradeSummary->historyForStudent($context['mahasiswa']) : collect(),
        ]);
    }

    public function rekomendasi(Request $request): View
    {
        $context = $this->data->context($request->user());
        $filterData = $this->semesterFilters($request);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $analysisPages = null;
        $recommendationTotals = collect();
        if ($context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'faktor_risiko', 'rekomendasi_intervensi', 'analisis_risiko', 'semester'])) {
            $query = $this->data->scopeAnalysis(AnalisisRisiko::query(), $context['mahasiswa']->id)
                ->when($semesterId, fn ($q, $id) => $q->where('analisis_risiko.semester_id', $id))
                ->whereHas('rekomendasiIntervensi');
            $recommendationTotals = (clone $query)
                ->join('rekomendasi_intervensi as grouped_recommendations', 'grouped_recommendations.analisis_risiko_id', '=', 'analisis_risiko.id')
                ->select('analisis_risiko.semester_id')
                ->selectRaw('COUNT(DISTINCT analisis_risiko.id) as analysis_count, COUNT(grouped_recommendations.id) as recommendation_count')
                ->groupBy('analisis_risiko.semester_id')->get()
                ->keyBy(fn ($row) => $row->semester_id ?? 'unassigned');
            // Paginate whole analyses: recommendations from one analysis stay together.
            $analysisPages = (clone $query)
                ->leftJoin('semester as recommendation_period', 'recommendation_period.id', '=', 'analisis_risiko.semester_id')
                ->select('analisis_risiko.*')
                ->with(['semester', 'rekomendasiIntervensi' => fn ($q) => $q->orderBy('id'), 'rekomendasiIntervensi.faktorRisiko'])
                ->orderByRaw('CASE WHEN analisis_risiko.semester_id IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('recommendation_period.tahun_ajaran')->orderByDesc('recommendation_period.periode')
                ->orderByDesc('analisis_risiko.dianalisis_pada')->orderByDesc('analisis_risiko.id')
                ->paginate(25)->withQueryString();
        }
        $recommendations = $analysisPages ? $analysisPages->getCollection()->flatMap->rekomendasiIntervensi : collect();

        // Legacy rows can point at a factor from a different analysis. Never
        // disclose that factor's explanation through an otherwise owned result.
        $recommendations->each(function ($item): void {
            if ($item->faktorRisiko && (int) $item->faktorRisiko->analisis_risiko_id !== (int) $item->analisis_risiko_id) {
                $item->setRelation('faktorRisiko', null);
            }
        });

        $recommendationGroups = $analysisPages
            ? $analysisPages->getCollection()->groupBy(fn ($analysis) => $analysis->semester_id ?? 'unassigned')
            : collect();

        return $this->feature('rekomendasi', 'Rekomendasi Intervensi', $context + $filterData
            + compact('recommendations', 'analysisPages', 'recommendationGroups', 'recommendationTotals'));
    }

    public function tindakLanjut(Request $request): View
    {
        $context = $this->data->context($request->user());
        $filterData = $this->semesterFilters($request);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $followUps = $context['mahasiswa'] && $this->data->hasTables(['users', 'program_studi', 'tindak_lanjut', 'rekomendasi_intervensi', 'analisis_risiko', 'semester'])
            ? TindakLanjut::query()
                ->whereHas('rekomendasiIntervensi.analisisRisiko', fn (Builder $query) => $this->data->scopeAnalysis($query, $context['mahasiswa']->id))
                ->when($semesterId, fn ($query, $id) => $query->whereHas('rekomendasiIntervensi.analisisRisiko', fn ($semester) => $semester->where('semester_id', $id)))
                ->with('rekomendasiIntervensi.analisisRisiko.semester')
                ->orderByDesc('dilakukan_pada')->orderByDesc('id')->paginate(25)->withQueryString()
            : collect();

        return $this->feature('tindak-lanjut', 'Tindak Lanjut', $context + $filterData + ['followUps' => $followUps]);
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
            'email' => ['nullable', 'email', 'max:255', 'not_regex:/@demo\.uninlar\.test$/i', Rule::unique('mahasiswa', 'email')->ignore($mahasiswa->id)],
            'no_telepon' => ['nullable', 'string', 'max:30'],
        ], ['email.not_regex' => 'Domain email tersebut tidak dapat digunakan untuk profil akademik ini.']);

        $mahasiswa->fill($validated)->save();

        return back()->with('status', 'Informasi kontak berhasil diperbarui.');
    }

    private function semesterFilters(Request $request): array
    {
        $semesters = Schema::hasTable('semester')
            ? Semester::query()->where('kode', 'not like', 'DEMO%')->orderByDesc('tahun_ajaran')->orderByDesc('periode')->get()
            : collect();
        $filters = $request->validate([
            'semester_id' => ['nullable', 'integer', Rule::in($semesters->pluck('id')->all())],
        ]);

        return compact('semesters', 'filters');
    }

    private function feature(string $view, string $title, array $data): View
    {
        return view("dashboard.mahasiswa.{$view}", $data + ['pageTitle' => $title, 'semesterAktif' => $this->data->activeSemester()]);
    }

    private function buildAcademicTrend(Collection $krsRecords): array
    {
        $periods = $krsRecords
            ->filter(fn (Krs $krs) => $krs->semester !== null)
            ->sortBy(fn (Krs $krs) => sprintf('%s-%02d', $krs->semester->tahun_ajaran, $krs->semester->periode))
            ->map(function (Krs $krs): array {
                $scores = Schema::hasTable('nilai')
                    ? $krs->details->filter(fn ($detail) => $krs->status === 'disetujui' && $detail->status === 'diambil')
                        ->map(fn ($detail) => $detail->nilai?->nilai_angka)->filter(fn ($value) => $value !== null)
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
