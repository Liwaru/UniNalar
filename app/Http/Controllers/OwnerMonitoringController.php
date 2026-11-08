<?php

namespace App\Http\Controllers;

use App\Models\KrsPengajuanRiwayat;
use App\Services\AcademicGradeSummary;
use App\Services\AdminAcademicData;
use App\Services\OwnerAcademicData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerMonitoringController extends Controller
{
    public function __construct(private readonly OwnerAcademicData $data, private readonly AcademicGradeSummary $grades) {}

    public function students(Request $request): View
    {
        $context = $this->data->context($request);
        $f = $context['filters'];
        $records = $context['available'] ? $this->data->searchStudent($this->data->students($f), $f['q'])->with('programStudi')->orderBy('nim')->paginate(20)->withQueryString() : null;
        $summaries = $records ? $this->grades->forStudents($records->getCollection(), $f['semester_id'] ?? null) : collect();

        return $this->page('students', 'Monitoring Mahasiswa', $context + compact('records', 'summaries'));
    }

    public function student(Request $request, int $student): View
    {
        $context = $this->data->context($request, false);
        abort_unless($context['available'], 503, 'Data akademik belum tersedia.');
        $f = $context['filters'];
        $record = $this->data->students($f)->with('programStudi')->findOrFail($student);
        $summary = $this->grades->forStudents(collect([$record]), $f['semester_id'] ?? null)->get($record->id);
        $krs = $this->data->krs($f)->where('mahasiswa_id', $record->id)->with('semester')->withCount('details')->orderByDesc('id')->paginate(15)->withQueryString();
        $finalCount = $this->data->finalizationAvailable() ? $this->data->grades($f)->whereHas('krsDetail.krs', fn ($q) => $q->where('mahasiswa_id', $record->id))->count() : null;
        $attendanceCount = $this->data->has('kehadiran') ? $this->data->attendance($f)->whereHas('krsDetail.krs', fn ($q) => $q->where('mahasiswa_id', $record->id))->count() : null;

        return $this->page('student', 'Ringkasan Mahasiswa', $context + compact('record', 'summary', 'krs', 'finalCount', 'attendanceCount'));
    }

    public function lecturers(Request $request): View
    {
        $context = $this->data->context($request);
        $f = $context['filters'];
        $records = $context['available'] ? $this->data->lecturers($f)->when($f['q'] !== '', fn ($q) => $q->where(fn ($s) => $s->where('nama', 'like', '%'.$f['q'].'%')->orWhere('nidn', 'like', '%'.$f['q'].'%')))
            ->with('programStudi')->withCount(['kelasKuliah' => fn ($q) => $q->whereIn('kelas_kuliah.id', $this->data->classes(['semester_id' => $f['semester_id'] ?? null])->select('kelas_kuliah.id'))])->orderBy('nama')->paginate(20)->withQueryString() : null;

        return $this->page('lecturers', 'Monitoring Dosen', $context + compact('records'));
    }

    public function lecturer(Request $request, int $lecturer): View
    {
        $context = $this->data->context($request, false);
        abort_unless($context['available'], 503, 'Data akademik belum tersedia.');
        $f = $context['filters'];
        $record = $this->data->lecturers($f)->with('programStudi')->findOrFail($lecturer);
        // Lecturer program and course program may differ; include every assigned class.
        $classScope = ['semester_id' => $f['semester_id'] ?? null];
        $records = $this->data->classCounts($this->data->classes($classScope)->where('dosen_id', $record->id), $classScope)->with(['mataKuliah', 'semester', 'dosen'])->orderByDesc('id')->paginate(20)->withQueryString();

        return $this->page('lecturer', 'Kelas Dosen', $context + compact('record', 'records'));
    }

    public function classes(Request $request): View
    {
        $context = $this->data->context($request);
        $f = $context['filters'];
        $records = $context['available'] ? $this->data->classCounts($this->data->classes($f), $f)
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($s) => $s->where('kode_kelas', 'like', '%'.$f['q'].'%')->orWhereHas('mataKuliah', fn ($m) => $m->where('nama', 'like', '%'.$f['q'].'%')->orWhere('kode', 'like', '%'.$f['q'].'%'))))
            ->with(['mataKuliah.programStudi', 'semester', 'dosen'])->orderByDesc('id')->paginate(20)->withQueryString() : null;

        return $this->page('classes', 'Monitoring Kelas', $context + compact('records'));
    }

    public function classRoom(Request $request, int $classRoom): View
    {
        $context = $this->data->context($request, false);
        abort_unless($context['available'], 503, 'Data akademik belum tersedia.');
        $f = $context['filters'];
        $record = $this->data->classCounts($this->data->classes($f), $f)->with(['mataKuliah.programStudi', 'semester', 'dosen'])->findOrFail($classRoom);
        $participants = $this->data->details(['semester_id' => $record->semester_id])->where('kelas_kuliah_id', $record->id)->with('krs.mahasiswa.programStudi');
        if ($this->data->finalizationAvailable()) {
            $participants->with(['nilai' => fn ($q) => $q->whereNotNull('final_at')]);
        }
        $records = $participants->orderBy('id')->paginate(20)->withQueryString();

        return $this->page('class', 'Peserta dan Penilaian Kelas', $context + compact('record', 'records'));
    }

    public function krs(Request $request): View
    {
        $context = $this->data->context($request, true, false, ['draft', 'menunggu', 'disetujui', 'ditolak']);
        $f = $context['filters'];
        $records = $context['available'] ? $this->data->krs($f)->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['q'] !== '', fn ($q) => $q->whereHas('mahasiswa', fn ($s) => $this->data->searchStudent($s, $f['q'])))
            ->with(['mahasiswa.programStudi', 'semester'])->withCount('details')->orderByDesc('id')->paginate(20)->withQueryString() : null;

        return $this->page('krs', 'Monitoring KRS', $context + compact('records'));
    }

    public function krsDetail(Request $request, int $krs): View
    {
        $context = $this->data->context($request, false);
        abort_unless($context['available'], 503, 'Data akademik belum tersedia.');
        $record = $this->data->krs($context['filters'])->with(['mahasiswa.programStudi', 'semester'])->findOrFail($krs);
        $records = $record->details()->with('kelasKuliah.mataKuliah')->orderBy('id')->paginate(20)->withQueryString();
        $historyAvailable = $this->data->has('krs_pengajuan_riwayat');
        $versions = $historyAvailable ? KrsPengajuanRiwayat::query()->where('krs_id', $record->id)->orderByDesc('versi')->paginate(10, ['*'], 'versions_page')->withQueryString() : null;

        return $this->page('krs-detail', 'Detail KRS', $context + compact('record', 'records', 'versions', 'historyAvailable'));
    }

    public function grades(Request $request): View
    {
        $context = $this->data->context($request, true, true);
        $available = $context['available'] && $this->data->finalizationAvailable();
        $f = $context['filters'];
        $query = $available ? $this->data->grades($f)->when($f['q'] !== '', fn ($q) => $q->whereHas('krsDetail.krs.mahasiswa', fn ($s) => $this->data->searchStudent($s, $f['q']))) : null;
        $distribution = $query ? (clone $query)->selectRaw('nilai_huruf, count(*) as total')->groupBy('nilai_huruf')->pluck('total', 'nilai_huruf') : collect();
        $records = $query ? $query->with(['krsDetail.krs.mahasiswa', 'krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])->orderByDesc('id')->paginate(20)->withQueryString() : null;

        return $this->page('grades', 'Monitoring Nilai Final', array_replace($context, compact('available', 'records', 'distribution')));
    }

    public function attendance(Request $request): View
    {
        $context = $this->data->context($request, true, true);
        $available = $context['available'] && $this->data->has('kehadiran');
        $f = $context['filters'];
        $query = $available ? $this->data->attendance($f)->when($f['q'] !== '', fn ($q) => $q->whereHas('krsDetail.krs.mahasiswa', fn ($s) => $this->data->searchStudent($s, $f['q']))) : null;
        $counts = $query ? (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();
        $records = $query ? $query->with(['krsDetail.krs.mahasiswa', 'krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString() : null;

        return $this->page('attendance', 'Monitoring Kehadiran', array_replace($context, compact('available', 'records', 'counts')));
    }

    public function risks(Request $request): View
    {
        $context = $this->data->context($request);
        $available = $context['available'] && $this->data->has('analisis_risiko');
        $f = $context['filters'];
        $records = $available ? $this->data->analyses($f)->when($f['q'] !== '', fn ($q) => $q->whereHas('mahasiswa', fn ($s) => $this->data->searchStudent($s, $f['q'])))
            ->with(['mahasiswa.programStudi', 'semester'])->orderByDesc('dianalisis_pada')->orderByDesc('id')->paginate(20)->withQueryString() : null;

        return $this->page('risks', 'Analisis Risiko Tersimpan', array_replace($context, compact('available', 'records')));
    }

    public function risk(Request $request, int $analysis): View
    {
        $context = $this->data->context($request, false);
        abort_unless($context['available'] && $this->data->has('analisis_risiko'), 503, 'Analisis risiko belum tersedia.');
        $record = $this->data->analyses($context['filters'])->with(['mahasiswa.programStudi', 'semester'])->findOrFail($analysis);
        $records = $this->data->has('faktor_risiko') ? $record->faktorRisiko()->orderByDesc('kontribusi')->orderBy('id')->paginate(20)->withQueryString() : null;

        return $this->page('risk', 'Detail Analisis Risiko', $context + compact('record', 'records'));
    }

    public function recommendations(Request $request): View
    {
        return $this->interventions($request, false);
    }

    public function followUps(Request $request): View
    {
        return $this->interventions($request, true);
    }

    private function interventions(Request $request, bool $followUp): View
    {
        $context = $this->data->context($request);
        $available = $context['available'] && $this->data->has('analisis_risiko', 'rekomendasi_intervensi') && (! $followUp || $this->data->has('tindak_lanjut'));
        $f = $context['filters'];
        $relation = $followUp ? 'rekomendasiIntervensi.analisisRisiko' : 'analisisRisiko';
        $query = $available ? ($followUp ? $this->data->followUps($f) : $this->data->recommendations($f)) : null;
        $statuses = $query ? (clone $query)->distinct()->orderBy('status')->pluck('status')->all() : [];
        $statusFilter = $request->validate(['status' => ['nullable', 'string', Rule::in($statuses)]], AdminAcademicData::messages(), ['status' => 'Status']);
        $context['statuses'] = $statuses;
        $context['filters'] += $statusFilter;
        $query?->when($statusFilter['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $records = $query ? $query->when($f['q'] !== '', fn ($q) => $q->whereHas($relation.'.mahasiswa', fn ($s) => $this->data->searchStudent($s, $f['q'])))
            ->with([$relation.'.mahasiswa.programStudi', $relation.'.semester'])->orderByDesc('id')->paginate(20)->withQueryString() : null;

        // Factors from a different analysis are never loaded through recommendations.
        return $this->page($followUp ? 'follow-ups' : 'recommendations', $followUp ? 'Monitoring Tindak Lanjut' : 'Rekomendasi Tersimpan', array_replace($context, compact('available', 'records')));
    }

    public function reports(Request $request): View
    {
        $context = $this->data->context($request);
        $f = $context['filters'];
        $query = $context['available'] ? app(AdminAcademicData::class)->masterQuery('program-studi')
            ->when($f['program_studi_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($s) => $s->where('nama', 'like', '%'.$f['q'].'%')->orWhere('kode', 'like', '%'.$f['q'].'%')))
            ->withCount(['mahasiswa as students_count' => fn ($q) => $q->whereIn('mahasiswa.id', $this->data->students($f)->select('mahasiswa.id')),
                'mahasiswa as approved_count' => fn ($q) => $q->whereIn('mahasiswa.id', $this->data->students($f)->select('mahasiswa.id'))->whereHas('krs', fn ($k) => $k->whereIn('krs.id', $this->data->krs($f)->where('status', 'disetujui')->select('krs.id'))),
            ])->addSelect(['classes_count' => $this->data->classes($f)->whereHas('mataKuliah', fn ($c) => $c->whereColumn('mata_kuliah.program_studi_id', 'program_studi.id'))->selectRaw('count(*)')]) : null;
        if ($query) {
            foreach (['menunggu', 'disetujui', 'ditolak'] as $status) {
                $query->addSelect([$status.'_count' => $this->data->krs($f)->where('status', $status)->whereHas('mahasiswa', fn ($s) => $s->whereColumn('mahasiswa.program_studi_id', 'program_studi.id'))->selectRaw('count(*)')]);
            }
            if ($this->data->finalizationAvailable()) {
                $query->addSelect(['final_count' => $this->data->grades($f)->whereHas('krsDetail.krs.mahasiswa', fn ($s) => $s->whereColumn('mahasiswa.program_studi_id', 'program_studi.id'))->selectRaw('count(*)')]);
            }
            if ($this->data->has('analisis_risiko')) {
                $query->addSelect(['analyses_count' => $this->data->analyses($f)->whereHas('mahasiswa', fn ($s) => $s->whereColumn('mahasiswa.program_studi_id', 'program_studi.id'))->selectRaw('count(*)')]);
                if ($this->data->has('rekomendasi_intervensi', 'tindak_lanjut')) {
                    $query->addSelect(['follow_ups_count' => $this->data->followUps($f)->whereHas('rekomendasiIntervensi.analisisRisiko.mahasiswa', fn ($s) => $s->whereColumn('mahasiswa.program_studi_id', 'program_studi.id'))->selectRaw('count(*)')]);
                }
            }
        }
        $records = $query?->orderBy('nama')->paginate(20)->withQueryString();

        return $this->page('reports', 'Laporan Akademik', $context + compact('records'));
    }

    private function page(string $view, string $pageTitle, array $context): View
    {
        return view('dashboard.owner.'.$view, $context + compact('pageTitle') + ['finalizationAvailable' => $this->data->finalizationAvailable()]);
    }
}
