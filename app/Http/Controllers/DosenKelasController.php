<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\KelasKuliah;
use App\Services\GradeFinalizationService;
use App\Services\LecturerAcademicData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DosenKelasController extends Controller
{
    public function __construct(private readonly LecturerAcademicData $data, private readonly GradeFinalizationService $grades) {}

    public function index(Request $request): View
    {
        $available = $this->data->available();
        $lecturer = $this->data->profile($request->user());
        $filterData = $this->data->filters($request, $lecturer);
        $filters = $filterData['filters'];
        $classes = $lecturer ? $this->data->classes($lecturer)
            ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id))
            ->when($filters['q'], fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('kode_kelas', 'like', "%{$search}%")
                ->orWhereHas('mataKuliah', fn ($course) => $course->where('kode', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%"))))
            ->with(['mataKuliah', 'semester'])
            ->withCount(['krsDetails as approved_count' => fn ($q) => $q->whereIn('id', $this->data->details($lecturer)->select('krs_detail.id'))])
            ->orderByDesc('semester_id')->orderBy('id')->paginate(15)->withQueryString() : collect();

        return view('dashboard.dosen.kelas', compact('available', 'lecturer', 'classes') + $filterData);
    }

    private function assigned(Request $request, int $kelas): KelasKuliah
    {
        abort_unless($this->data->available(), 503, 'Schema akademik belum tersedia.');
        $lecturer = $this->data->profile($request->user());
        abort_unless($lecturer, 404);

        return $this->data->classes($lecturer)->with(['mataKuliah.programStudi', 'semester', 'dosen'])->findOrFail($kelas);
    }

    public function show(Request $request, int $kelas): View
    {
        $class = $this->assigned($request, $kelas);
        $lecturer = $this->data->profile($request->user());
        $details = $this->data->details($lecturer)->where('kelas_kuliah_id', $class->id);
        $gradeAvailable = $this->grades->workflowAvailable();
        $stats = ['participants' => (clone $details)->count(),
            'final' => $gradeAvailable ? (clone $details)->whereHas('nilai', fn ($q) => $q->whereNotNull('final_at'))->count() : null,
            'ungraded' => $gradeAvailable ? (clone $details)->whereDoesntHave('nilai')->count() : null];
        $stats['draft'] = $gradeAvailable ? $stats['participants'] - $stats['final'] - $stats['ungraded'] : null;

        return view('dashboard.dosen.kelas-detail', compact('class', 'stats', 'gradeAvailable'));
    }

    public function participants(Request $request, int $kelas): View
    {
        $class = $this->assigned($request, $kelas);
        $lecturer = $this->data->profile($request->user());
        $filterData = $this->data->filters($request, $lecturer);
        $gradeAvailable = $this->grades->workflowAvailable();
        $request->validate([
            'semester_id' => ['nullable', 'integer', Rule::in([$class->semester_id])],
            'kelas_id' => ['nullable', 'integer', Rule::in([$class->id])],
        ]);
        $relations = ['krs.mahasiswa.programStudi'];
        if ($gradeAvailable) {
            $relations[] = 'nilai';
        }
        $participants = $this->data->filterParticipants($this->data->details($lecturer)->where('kelas_kuliah_id', $class->id), $filterData['filters'], $gradeAvailable)
            ->with($relations)->orderBy('id')->paginate(25)->withQueryString();

        return view('dashboard.dosen.peserta', compact('class', 'participants', 'gradeAvailable') + $filterData);
    }

    public function attendance(Request $request, int $kelas): View
    {
        $class = $this->assigned($request, $kelas);
        $lecturer = $this->data->profile($request->user());
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'pertemuan_ke' => ['nullable', 'integer', 'between:1,65535']]);
        $available = Schema::hasTable('kehadiran');
        $query = $available ? Kehadiran::query()->whereIn('krs_detail_id', $this->data->details($lecturer)->where('kelas_kuliah_id', $class->id)->select('krs_detail.id'))
            ->when($filters['pertemuan_ke'] ?? null, fn ($q, $meeting) => $q->where('pertemuan_ke', $meeting))
            ->when(trim($filters['q'] ?? ''), fn ($q, $search) => $q->whereHas('krsDetail.krs.mahasiswa', fn ($s) => $s
                ->where(fn ($student) => $student->where('nama', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%")))) : null;
        $statuses = $query ? (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();
        $records = $query ? $query->with('krsDetail.krs.mahasiswa')->orderByDesc('tanggal')->orderByDesc('id')->paginate(25)->withQueryString() : collect();

        return view('dashboard.dosen.kehadiran', compact('class', 'available', 'records', 'statuses', 'filters'));
    }
}
