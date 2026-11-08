<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\Krs;
use App\Services\GradeFinalizationService;
use App\Services\LecturerAcademicData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DosenDashboardController extends Controller
{
    public function index(Request $request, LecturerAcademicData $data, GradeFinalizationService $finalization): View
    {
        $available = $data->available();
        $lecturer = $data->profile($request->user());
        $filterData = $data->filters($request, $lecturer);
        $semesterId = $filterData['filters']['semester_id'] ?? null;
        $classQuery = $lecturer ? $data->classes($lecturer)->when($semesterId, fn ($q, $id) => $q->where('semester_id', $id)) : null;
        $classes = $classQuery ? (clone $classQuery)->with(['mataKuliah', 'semester'])->orderByDesc('semester_id')->orderByDesc('id')->limit(6)->get() : collect();
        $stats = null;
        if ($lecturer && $classes->isNotEmpty()) {
            $details = $data->details($lecturer)->whereIn('kelas_kuliah_id', (clone $classQuery)->select('kelas_kuliah.id'));
            $finalCount = $finalization->workflowAvailable() ? (clone $details)->whereHas('nilai', fn ($q) => $q->whereNotNull('final_at'))->count() : null;
            $stats = [
                'classes' => (clone $classQuery)->count(),
                'ungraded' => $finalCount === null ? null : (clone $details)->whereDoesntHave('nilai')->count(),
                'attendance' => Schema::hasTable('kehadiran') ? Kehadiran::query()->whereIn('krs_detail_id', (clone $details)->select('krs_detail.id'))->count() : null,
                'students' => Krs::query()->whereIn('id', (clone $details)->select('krs_id'))->distinct()->count('mahasiswa_id'),
                'final' => $finalCount,
                'pending' => $finalCount === null ? null : (clone $details)->count() - $finalCount,
            ];
        }

        return view('dashboard.dosen.index', compact('available', 'lecturer', 'classes', 'stats') + $filterData);
    }
}
