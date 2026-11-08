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
        $filterData = $this->data->filters($request, $lecturer);
        $selectedClass = ! empty($filterData['filters']['kelas_id'])
            ? $filterData['classOptions']->firstWhere('id', (int) $filterData['filters']['kelas_id'])
            : $filterData['classOptions']->first();
        $participants = $lecturer && $available && $selectedClass
            ? $this->data->filterParticipants($this->data->details($lecturer)->where('kelas_kuliah_id', $selectedClass->id), $filterData['filters'], true)
                ->with(['krs.mahasiswa', 'nilai'])->orderBy('id')->paginate(25)->appends(['kelas_id' => $selectedClass->id])->withQueryString()
            : collect();
        $classes = $selectedClass && $available
            ? collect([$selectedClass->setRelation('krsDetails', $participants->getCollection())]) : collect();

        return view('dashboard.dosen.nilai', [
            'lecturer' => $lecturer,
            'classes' => $classes,
            'available' => $available,
            'gradePoints' => config('academic.grade_points', []),
            'participants' => $participants,
            'selectedClass' => $selectedClass,
        ] + $filterData);
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
