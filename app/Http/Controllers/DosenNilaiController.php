<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Nilai;
use App\Services\GradeFinalizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DosenNilaiController extends Controller
{
    public function __construct(private readonly GradeFinalizationService $finalization) {}

    public function index(Request $request): View
    {
        $lecturer = Dosen::query()->where('user_id', $request->user()->id)->where('aktif', true)->first();
        $available = Schema::hasTable('nilai') && $this->finalization->workflowAvailable();
        $classes = $lecturer && $available
            ? KelasKuliah::query()->where('dosen_id', $lecturer->id)
                ->whereHas('mataKuliah', fn (Builder $course) => $course->where('kode', 'not like', 'DEMO%')
                    ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO')))
                ->whereHas('semester', fn (Builder $semester) => $semester->where('kode', 'not like', 'DEMO%'))
                ->with(['mataKuliah', 'semester', 'krsDetails' => fn ($details) => $details
                    ->whereHas('krs', fn (Builder $krs) => $krs->where('status', 'disetujui')
                        ->whereHas('mahasiswa', fn (Builder $student) => $student->where('nim', 'not like', 'DEMO%')
                            ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO'))))
                    ->with(['krs.mahasiswa', 'nilai'])])
                ->orderByDesc('semester_id')->orderBy('mata_kuliah_id')->get()
            : collect();

        return view('dashboard.dosen.nilai', [
            'lecturer' => $lecturer,
            'classes' => $classes,
            'available' => $available,
        ]);
    }

    public function finalize(Request $request, Nilai $nilai): RedirectResponse
    {
        $this->finalization->finalize($request->user(), $nilai->id);

        return back()->with('status', 'Nilai berhasil difinalisasi.');
    }
}
