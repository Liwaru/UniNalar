@extends('layouts.student')
@section('title', 'Analisis Risiko')
@section('page-title', 'Analisis Risiko')
@section('page-subtitle', 'Riwayat analisis dan faktor penjelas yang tersimpan.')
@section('content')
    @php
        $aiReadiness = app(\App\Services\GroqLivePolicy::class)->readiness($mahasiswa);
        $analysisEnabled = $aiReadiness['enabled'];
    @endphp
    @include('dashboard.mahasiswa.partials.semester-filter')
    <section class="student-section" aria-labelledby="ai-analysis-title">
        <h2 id="ai-analysis-title">Penjelasan indikator akademik</h2>
        <p>AI membantu menjelaskan indikator dan menyarankan langkah belajar. Hasilnya dapat keliru dan bukan skor, kategori risiko resmi, atau keputusan akademik.</p>
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        <p class="data-meta" role="status" data-ai-readiness>{{ $aiReadiness['message'] }}</p>
        <form method="POST" action="{{ route('dashboard.mahasiswa.analisis-risiko.ai') }}" class="student-ai-form" data-ai-analysis data-ai-enabled="{{ $analysisEnabled ? 'true' : 'false' }}">
            @csrf
            <label for="ai-semester">Semester analisis</label>
            <select id="ai-semester" name="semester_id" required>
                <option value="">Pilih semester</option>
                @foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected((string) old('semester_id', $filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->kode }}</option>@endforeach
            </select>
            <label for="ai-consent"><input id="ai-consent" type="checkbox" name="persetujuan" value="1" required> Saya mengizinkan pengiriman indikator agregat tanpa identitas ke layanan AI untuk analisis ini.</label>
            <button type="submit" @disabled(!$analysisEnabled)>Jelaskan indikator</button>
            <p class="data-meta" data-ai-status role="status">Hasil dengan asal yang sesuai digunakan kembali bila indikator tidak berubah. Riwayat tetap tersedia ketika analisis baru dinonaktifkan.</p>
        </form>
    </section>
    <section class="student-card analysis-chart-panel" aria-labelledby="analysis-history-title">
        <div class="student-section-heading"><div><h2 id="analysis-history-title">Perkembangan indeks prestasi</h2><p>Catatan akademik terkini · bukan hasil generasi AI</p></div></div>
        <p class="data-meta">Semua semester dengan catatan nilai final pada KRS disetujui. IPK kumulatif dihitung hingga tiap semester; nilainya dapat berbeda dari snapshot saat analisis.</p>
        @if ($academicHistory->isNotEmpty())
            <div class="analysis-chart-frame"><canvas id="analysisAcademicChart" role="img" aria-label="Grafik IPS dan IPK per semester, skala nol sampai empat" aria-describedby="analysis-history-table"></canvas></div>
            <script type="application/json" id="analysisAcademicData">@json($academicHistory)</script>
            <details class="trend-data" @if ($academicHistory->count() === 1) open @endif>
                <summary>Lihat angka akademik per semester</summary>
                <table class="data-table" id="analysis-history-table"><caption>Nilai akademik terkini. Data yang tidak tersedia tidak diganti dengan nol.</caption><thead><tr><th scope="col">Semester</th><th scope="col">IPS</th><th scope="col">IPK hingga semester</th></tr></thead><tbody>
                    @foreach ($academicHistory as $row)<tr><th scope="row">{{ $row['semester'] }}</th><td>{{ $row['ips'] !== null ? number_format($row['ips'], 2, ',', '.') : 'Data belum tersedia' }}</td><td>{{ $row['ipk'] !== null ? number_format($row['ipk'], 2, ',', '.') : 'Data belum tersedia' }}</td></tr>@endforeach
                </tbody></table>
            </details>
            @if ($academicHistory->count() === 1)<p class="data-meta">Baru satu semester tersedia; belum ada tren antarsemester.</p>@endif
        @else
            <p class="data-meta">Data belum tersedia. Grafik akan tampil setelah ada catatan nilai final.</p>
        @endif
    </section>
    @forelse ($analyses as $analysis)
        @include('dashboard.mahasiswa.partials.analysis-result')
    @empty
        <div class="empty-state" role="status"><strong>Analisis risiko belum tersedia</strong><span>Dashboard hanya menampilkan analisis yang sudah tersimpan. Skor tidak dihitung otomatis.</span></div>
    @endforelse
    @if ($analyses instanceof \Illuminate\Contracts\Pagination\Paginator)<div class="student-pagination">{{ $analyses->links() }}</div>@endif
@endsection
