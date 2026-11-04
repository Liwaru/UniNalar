<?php

namespace App\Http\Controllers;

use App\Services\KrsSubmissionService;
use App\Services\MahasiswaOperationalData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if ($student && $semester && $this->workflow->workflowAvailable()) {
            $records = $this->operationalData->krsQuery($student->id)
                ->with(['semester', 'details.kelasKuliah.mataKuliah'])
                ->orderByDesc('semester_id')->get();
            $krs = $records->firstWhere('semester_id', $semester->id);
            if ($krs) {
                $krs->load(['riwayatPengajuan' => fn ($query) => $query->orderByDesc('versi')]);
            }
            $available = $this->workflow->availableClasses($student, $semester)->get()
                ->filter(fn ($class) => ! $krs?->details->contains('kelas_kuliah_id', $class->id))
                ->values();
            $histories = $krs?->riwayatPengajuan ?? collect();
        }

        return view('dashboard.mahasiswa.krs', [
            ...$context,
            'krs' => $records,
            'activeKrs' => $krs,
            'activeSemester' => $semester,
            'semesterAktif' => $semester,
            'availableClasses' => $available,
            'histories' => $histories,
            'workflowAvailable' => $this->workflow->workflowAvailable(),
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
