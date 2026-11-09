<?php

namespace App\Http\Controllers;

use App\Services\AcademicGradeSummary;
use App\Services\AdminAcademicData;
use App\Services\OwnerAcademicData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function index(Request $request, AcademicGradeSummary $grades, OwnerAcademicData $data): View
    {
        $context = $data->context($request, false);
        $filters = $context['filters'];
        $stats = $totals = null;
        $krsStatuses = $riskDistribution = $recommendations = $followUps = collect();
        $metrics = [];
        $moduleAvailable = ['Status KRS' => $context['available'], 'Risiko dari analisis terbaru per mahasiswa' => $data->has('analisis_risiko'), 'Rekomendasi tercatat' => $data->has('analisis_risiko', 'rekomendasi_intervensi'), 'Tindak lanjut tercatat' => $data->has('analisis_risiko', 'rekomendasi_intervensi', 'tindak_lanjut')];
        if ($context['available']) {
            // Profile totals are institution/program totals, independent of semester.
            $profileScope = ['program_studi_id' => $filters['program_studi_id'] ?? null];
            $totals = [
                'students' => $data->students($profileScope)->count(),
                'lecturers' => $data->lecturers($profileScope)->count(),
                'programs' => $context['programs']->when($filters['program_studi_id'] ?? null, fn ($p, $id) => $p->where('id', $id))->count(),
            ];
            $krsStatuses = $data->krs($filters)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $students = $data->students($profileScope)->get(['mahasiswa.id']);
            if ($students->isNotEmpty()) {
                $summaries = $grades->forStudents($students, $filters['semester_id'] ?? null);
                $ips = $summaries->pluck('ips')->filter(fn ($v) => $v !== null);
                $ipk = $summaries->pluck('ipk')->filter(fn ($v) => $v !== null);
                $stats = ['students' => $students->count(), 'krs' => $krsStatuses->sum(), 'ips' => $ips->isEmpty() ? null : $ips->avg(), 'ipk' => $ipk->isEmpty() ? null : $ipk->avg(), 'graded_students' => $ipk->count()];
            }
            $metrics = [
                'courses' => app(AdminAcademicData::class)->masterQuery('mata-kuliah')->when($filters['program_studi_id'] ?? null, fn ($q, $id) => $q->where('program_studi_id', $id))->count(),
                'classes' => $data->classes($filters)->count(),
                'official' => $data->details($filters)->count(),
                'final' => $data->finalizationAvailable() ? $data->grades($filters)->count() : null,
                'attendance' => $data->has('kehadiran') ? $data->attendance($filters)->count() : null,
            ];
            if ($data->has('analisis_risiko')) {
                $analyses = $data->analyses($filters);
                $riskDistribution = (clone $analyses)->orderByDesc('dianalisis_pada')->orderByDesc('id')->get(['id', 'mahasiswa_id', 'kategori'])->unique('mahasiswa_id')->countBy(fn ($analysis) => $analysis->kategori ?? 'Belum diklasifikasikan');
                if ($data->has('rekomendasi_intervensi')) {
                    $recommendations = $data->recommendations($filters)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                    if ($data->has('tindak_lanjut')) {
                        $followUps = $data->followUps($filters)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                    }
                }
            }
        }

        return view('dashboard.owner.index', $context + compact('moduleAvailable', 'stats', 'totals', 'metrics', 'krsStatuses', 'riskDistribution', 'recommendations', 'followUps'));
    }
}
