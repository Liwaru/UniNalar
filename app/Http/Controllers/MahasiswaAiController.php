<?php

namespace App\Http\Controllers;

use App\Services\GroqAcademicAnalysis;
use App\Services\MahasiswaOperationalData;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MahasiswaAiController extends Controller
{
    public function store(Request $request, MahasiswaOperationalData $data, GroqAcademicAnalysis $analysis)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'integer', Rule::exists('semester', 'id')->where(fn ($q) => $q->where('kode', 'not like', 'DEMO%'))],
            'persetujuan' => ['required', 'accepted'],
            'mahasiswa_id' => ['prohibited'], 'indikator' => ['prohibited'],
        ]);
        $student = $data->context($request->user())['mahasiswa'];
        abort_unless($student, 404);
        try {
            $result = $analysis->generate($student, (int) $validated['semester_id']);
        } catch (DomainException $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        } catch (\RuntimeException) {
            return back()->withErrors(['ai' => 'Analisis sementara tidak tersedia. Periksa riwayat sebelum mencoba lagi; permintaan tidak diulang otomatis.']);
        }

        return redirect()->route('dashboard.mahasiswa.analisis-risiko', ['semester_id' => $validated['semester_id']])
            ->with('status', $result->wasRecentlyCreated
                ? 'Analisis deskriptif berhasil disimpan.'
                : 'Hasil tersimpan digunakan kembali. Tidak ada permintaan AI baru.');
    }
}
