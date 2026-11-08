<?php

namespace App\Http\Controllers;

use App\Services\AdminAcademicData;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminKrsReviewController extends Controller
{
    public function __construct(private readonly KrsSubmissionService $workflow, private readonly KrsDecisionService $decisions, private readonly AdminAcademicData $data) {}

    public function index(Request $request): View
    {
        $available = $this->workflow->workflowAvailable();
        $filterData = $this->data->available() ? $this->data->filters($request, ['program_studi_id', 'semester_id']) : ['filters' => ['q' => ''], 'filterOptions' => []];
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['menunggu', 'disetujui', 'ditolak', 'semua'])],
            'cakupan' => ['nullable', Rule::in(['terbaru', 'semua_versi'])],
        ], AdminAcademicData::messages(), ['status' => 'Status pengajuan', 'cakupan' => 'Cakupan riwayat']) + $filterData['filters'];
        $filters['status'] = $filters['status'] ?? 'menunggu';
        $filters['cakupan'] = $filters['cakupan'] ?? 'terbaru';
        $query = $available ? $this->data->submissions()
            ->when($filters['cakupan'] === 'terbaru', fn ($q) => $q->whereRaw('versi = (select MAX(v.versi) from krs_pengajuan_riwayat v where v.krs_id = krs_pengajuan_riwayat.krs_id)'))
            ->when($filters['status'] !== 'semua', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['program_studi_id'] ?? null, fn ($q, $id) => $q->whereHas('krs.mahasiswa', fn ($s) => $s->where('program_studi_id', $id)))
            ->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->whereHas('krs', fn ($s) => $s->where('semester_id', $id)))
            ->when($filters['q'], fn ($q, $search) => $q->whereHas('krs.mahasiswa', fn ($s) => $s->where(fn ($s) => $s->where('nim', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%")))) : null;
        $submissions = $query ? $query->with(['diajukanOleh', 'krs.mahasiswa.programStudi', 'krs.semester'])->orderBy('diajukan_pada')->orderBy('id')->paginate(20)->withQueryString() : null;
        $filterOptions = $filterData['filterOptions'];

        return view('dashboard.admin-akademik.krs-review', compact('available', 'submissions', 'filters', 'filterOptions'));
    }

    public function show(int $history): View
    {
        abort_unless($this->workflow->workflowAvailable(), 503, 'Detail pengajuan KRS belum tersedia.');
        $submission = $this->data->submissions()->with(['diajukanOleh', 'diputuskanOleh', 'krs.mahasiswa.programStudi', 'krs.semester'])->findOrFail($history);
        $versions = $this->data->submissions()->where('krs_id', $submission->krs_id)->orderByDesc('versi')->paginate(15);

        return view('dashboard.admin-akademik.krs-detail', compact('submission', 'versions'));
    }

    public function decide(Request $request, int $history, string $decision): Response
    {
        if (! $this->workflow->workflowAvailable()) {
            return response('Alur pengajuan KRS belum tersedia sampai schema pendukung diterapkan.', 503);
        }

        $data = $request->validate(['catatan_keputusan' => ['nullable', 'string', 'max:2000']], AdminAcademicData::messages(), ['catatan_keputusan' => 'Catatan keputusan']);
        $this->decisions->decide($history, $request->user(), $decision, $data['catatan_keputusan'] ?? null);

        return back()->with('status', 'Pengajuan KRS berhasil '.$decision.'.');
    }
}
