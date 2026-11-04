<?php

namespace App\Http\Controllers;

use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\LecturerAcademicData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DosenNilaiController extends Controller
{
    public function __construct(private readonly GradeFinalizationService $finalization, private readonly LecturerAcademicData $data, private readonly GradeEntryService $entries) {}

    public function index(Request $request): View
    {
        $available = $this->finalization->workflowAvailable();
        $lecturer = $this->data->profile($request->user());
        $classes = $lecturer && $available
            ? $this->data->classes($lecturer)
                ->with(['mataKuliah', 'semester', 'krsDetails' => fn ($details) => $details
                    ->whereIn('id', $this->data->details($lecturer)->select('krs_detail.id'))
                    ->with(['krs.mahasiswa', 'nilai'])])
                ->orderByDesc('semester_id')->orderBy('mata_kuliah_id')->paginate(10)
            : collect();

        return view('dashboard.dosen.nilai', [
            'lecturer' => $lecturer,
            'classes' => $classes,
            'available' => $available,
            'gradePoints' => config('academic.grade_points', []),
        ]);
    }

    public function save(Request $request, int $detail): RedirectResponse
    {
        $this->entries->save($request->user(), $detail, $request->only(['nilai_angka', 'nilai_huruf', 'revision']));

        return back()->with('status', 'Nilai tersimpan. Nilai belum dikunci sampai difinalisasi.');
    }

    public function finalize(Request $request, int $nilai): RedirectResponse
    {
        $this->finalization->finalize($request->user(), $nilai);

        return back()->with('status', 'Nilai berhasil difinalisasi.');
    }
}
