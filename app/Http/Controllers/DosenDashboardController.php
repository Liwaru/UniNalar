<?php

namespace App\Http\Controllers;

use App\Models\Krs;
use App\Services\GradeFinalizationService;
use App\Services\LecturerAcademicData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DosenDashboardController extends Controller
{
    public function index(Request $request, LecturerAcademicData $data, GradeFinalizationService $finalization): View
    {
        $available = $data->available();
        $lecturer = $data->profile($request->user());
        $classes = $lecturer ? $data->classes($lecturer)->with(['mataKuliah', 'semester'])->orderByDesc('semester_id')->limit(6)->get() : collect();
        $stats = null;
        if ($lecturer && $classes->isNotEmpty()) {
            $details = $data->details($lecturer);
            $finalCount = $finalization->workflowAvailable() ? (clone $details)->whereHas('nilai', fn ($q) => $q->whereNotNull('final_at'))->count() : null;
            $stats = [
                'classes' => $data->classes($lecturer)->count(),
                'students' => Krs::query()->whereIn('id', (clone $details)->select('krs_id'))->distinct()->count('mahasiswa_id'),
                'final' => $finalCount,
                'pending' => $finalCount === null ? null : (clone $details)->count() - $finalCount,
            ];
        }

        return view('dashboard.dosen.index', compact('available', 'lecturer', 'classes', 'stats'));
    }
}
