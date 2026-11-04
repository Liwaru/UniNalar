<?php

namespace App\Http\Controllers;

use App\Models\KrsPengajuanRiwayat;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use App\Services\OperationalAcademicData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminKrsReviewController extends Controller
{
    public function __construct(private readonly KrsSubmissionService $workflow, private readonly KrsDecisionService $decisions) {}

    public function index(): View
    {
        $available = $this->workflow->workflowAvailable();
        $submissions = $available
            ? KrsPengajuanRiwayat::query()->where('status', 'menunggu')
                ->whereHas('krs', fn (Builder $krs) => OperationalAcademicData::scopeKrs($krs)->where('status', 'menunggu'))
                ->with(['diajukanOleh', 'krs.mahasiswa.programStudi', 'krs.semester'])
                ->orderBy('diajukan_pada')->orderBy('id')->paginate(20)
            : null;

        return view('dashboard.admin-akademik.krs-review', compact('available', 'submissions'));
    }

    public function decide(Request $request, int $history, string $decision): Response
    {
        if (! $this->workflow->workflowAvailable()) {
            return response('Alur pengajuan KRS belum tersedia sampai schema pendukung diterapkan.', 503);
        }

        $data = $request->validate(['catatan_keputusan' => ['nullable', 'string', 'max:2000']]);
        $this->decisions->decide($history, $request->user(), $decision, $data['catatan_keputusan'] ?? null);

        return back()->with('status', 'Pengajuan KRS berhasil '.$decision.'.');
    }
}
