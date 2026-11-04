<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\RekomendasiIntervensi;
use App\Models\Semester;
use App\Models\TindakLanjut;
use App\Services\AcademicGradeSummary;
use App\Services\OperationalAcademicData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerDashboardController extends Controller
{
    public function index(Request $request, AcademicGradeSummary $grades): View
    {
        $available = collect(['users', 'program_studi', 'mahasiswa', 'dosen', 'mata_kuliah', 'kelas_kuliah', 'semester', 'krs', 'krs_detail'])
            ->every(fn ($table) => Schema::hasTable($table));
        $programs = Schema::hasTable('program_studi') ? ProgramStudi::query()->where('kode', 'not like', '%-DEMO')->orderBy('nama')->get() : collect();
        $semesters = Schema::hasTable('semester') ? Semester::query()->where('kode', 'not like', 'DEMO%')->orderByDesc('id')->get() : collect();
        $filters = $available ? $request->validate([
            'program_studi_id' => ['nullable', 'integer', Rule::in($programs->pluck('id')->all())],
            'semester_id' => ['nullable', 'integer', Rule::in($semesters->pluck('id')->all())],
        ]) : [];
        $stats = null;
        $krsStatuses = $riskDistribution = $recommendations = $followUps = collect();

        if ($available) {
            $students = OperationalAcademicData::students()->when($filters['program_studi_id'] ?? null, fn ($q, $id) => $q->where('program_studi_id', $id))->get(['mahasiswa.id']);
            if ($students->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                $krs = OperationalAcademicData::krs()->whereIn('mahasiswa_id', $studentIds)
                    ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id));
                $krsStatuses = (clone $krs)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                $summaries = $grades->forStudents($students);
                $ips = $summaries->pluck('ips')->filter(fn ($v) => $v !== null);
                $ipk = $summaries->pluck('ipk')->filter(fn ($v) => $v !== null);
                $stats = ['students' => $students->count(), 'krs' => $krsStatuses->sum(), 'ips' => $ips->isEmpty() ? null : $ips->avg(), 'ipk' => $ipk->isEmpty() ? null : $ipk->avg(), 'graded_students' => $ipk->count()];

                if (Schema::hasTable('analisis_risiko')) {
                    $analyses = OperationalAcademicData::analyses()->whereIn('mahasiswa_id', $studentIds)
                        ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('semester_id', $id));
                    $riskDistribution = (clone $analyses)->orderByDesc('dianalisis_pada')->orderByDesc('id')
                        ->get(['id', 'mahasiswa_id', 'kategori'])->unique('mahasiswa_id')->countBy('kategori');
                    if (Schema::hasTable('rekomendasi_intervensi')) {
                        $recommendationQuery = RekomendasiIntervensi::query()->whereIn('analisis_risiko_id', (clone $analyses)->select('analisis_risiko.id'));
                        $recommendations = (clone $recommendationQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                        if (Schema::hasTable('tindak_lanjut')) {
                            $followUps = TindakLanjut::query()->whereIn('rekomendasi_intervensi_id', (clone $recommendationQuery)->select('rekomendasi_intervensi.id'))
                                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                        }
                    }
                }
            }
        }

        return view('dashboard.owner.index', compact('available', 'programs', 'semesters', 'filters', 'stats', 'krsStatuses', 'riskDistribution', 'recommendations', 'followUps'));
    }
}
