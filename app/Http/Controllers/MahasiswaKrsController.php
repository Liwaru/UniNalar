<?php

namespace App\Http\Controllers;

use App\Services\KrsSubmissionService;
use App\Services\MahasiswaOperationalData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MahasiswaKrsController extends Controller
{
    public function __construct(
        private readonly KrsSubmissionService $workflow,
        private readonly MahasiswaOperationalData $operationalData,
    ) {}

    public function index(Request $request): View
    {
        $context = $this->operationalData->context($request->user());
        $student = $context['mahasiswa'];
        $semester = $this->operationalData->activeSemester();
        $available = collect();
        $krs = null;
        $records = collect();
        $histories = collect();

        $workflowAvailable = $this->workflow->workflowAvailable();
        // Readable audit records do not depend on mutation-readiness constraints.
        $historyAvailable = array_diff(['krs_id', 'versi', 'diajukan_pada', 'status', 'snapshot_kelas', 'catatan_keputusan'], Schema::getColumnListing('krs_pengajuan_riwayat')) === [];
        if ($student && $this->operationalData->hasTables(['users', 'program_studi', 'dosen', 'krs', 'krs_detail', 'kelas_kuliah', 'mata_kuliah', 'semester'])) {
            $relations = ['semester', 'details.kelasKuliah.mataKuliah'];
            if ($historyAvailable) {
                $relations['riwayatPengajuan'] = fn ($query) => $query->orderByDesc('versi');
            }
            $records = $this->operationalData->krsQuery($student->id)
                ->with($relations)->orderByDesc('semester_id')->get();
            $krs = $semester ? $records->firstWhere('semester_id', $semester->id) : null;
            $histories = $historyAvailable
                ? $records->flatMap(fn ($record) => $record->riwayatPengajuan)->sortByDesc('diajukan_pada')->values()
                : collect();
            if ($semester && $workflowAvailable) {
                $available = $this->workflow->availableClasses($student, $semester)
                    ->withCount(['krsDetails as reserved_places' => fn ($query) => $query->whereHas('krs', fn ($krs) => $krs->whereIn('status', ['menunggu', 'disetujui']))])->get()
                    ->filter(fn ($class) => ! $krs?->details->contains('kelas_kuliah_id', $class->id))->values();
            }
        }

        return view('dashboard.mahasiswa.krs', [
            ...$context,
            'krs' => $records,
            'activeKrs' => $krs,
            'activeSemester' => $semester,
            'semesterAktif' => $semester,
            'availableClasses' => $available,
            'histories' => $histories,
            'workflowAvailable' => $workflowAvailable,
            'registrationOpen' => $semester ? $this->workflow->registrationIsOpen($semester) : false,
            'pageTitle' => 'KRS dan Mata Kuliah',
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate(['kelas_kuliah_id' => ['required', 'integer']]);
        $this->workflow->addClass($request->user(), (int) $validated['kelas_kuliah_id']);

        return back()->with('status', 'Kelas ditambahkan ke KRS draft.');
    }

    public function remove(Request $request, int $detail): RedirectResponse
    {
        $this->workflow->removeClass($request->user(), $detail);

        return back()->with('status', 'Kelas dihapus dari KRS draft.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $history = $this->workflow->submit($request->user());

        return back()->with('status', 'Pengajuan KRS versi '.$history->versi.' berhasil dikirim untuk ditinjau.');
    }
}
