<?php

namespace App\Http\Controllers;

use App\Services\AdminAcademicData;
use App\Services\KrsSubmissionService;
use App\Services\OperationalAcademicData;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(AdminAcademicData $data, KrsSubmissionService $workflow): View
    {
        $available = $data->available();
        $totals = $available ? collect(['program-studi', 'semester', 'mata-kuliah', 'dosen', 'mahasiswa', 'kelas-kuliah'])
            ->mapWithKeys(fn ($key) => [$key => $data->masterQuery($key)->count()]) : collect();
        $krsStatuses = $available ? OperationalAcademicData::krs()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();
        $workflowAvailable = $workflow->workflowAvailable();

        return view('dashboard.admin-akademik.index', compact('available', 'totals', 'krsStatuses', 'workflowAvailable'));
    }
}
