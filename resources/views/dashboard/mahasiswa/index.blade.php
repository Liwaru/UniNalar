@extends('layouts.student')

@section('title', 'Dashboard Mahasiswa')
@section('page-title', $mahasiswa?->nama ?? 'Dashboard Mahasiswa')
@section('page-subtitle')
    @if ($mahasiswa)
        {{ $mahasiswa->nim }}@if ($mahasiswa->programStudi) · {{ $mahasiswa->programStudi->nama }}@endif
    @elseif ($isDemoProfile)
        Ringkasan akademik belum tersedia
    @else
        Ringkasan akademik Anda
    @endif
@endsection

@section('content')
    @php
        $riskCategory = $analisisTerbaru?->kategori;
        $riskClass = $riskCategory ? strtolower(str_replace([' ', '_'], '-', $riskCategory)) : '';
    @endphp
    <section class="student-section" aria-labelledby="summary-title">
        <div class="student-section-heading"><div><h2 id="summary-title">Ringkasan akademik</h2><p>Ringkasan dari catatan database yang tertaut ke profil Anda.</p></div></div>
        <div class="summary-grid">
            <article class="summary-card">
                <div class="summary-card-top"><span>Rata-rata nilai</span><span class="summary-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M7 15l3-4 3 2 5-7"/></svg></span></div>
                @if ($rataRataNilai !== null)<div class="summary-value">{{ number_format((float) $rataRataNilai, 2) }}</div><div class="summary-note">Dari {{ $jumlahNilai }} nilai · bukan IPS/IPK</div>@else<div class="summary-value is-empty">Belum tersedia</div><div class="summary-note">Data nilai angka belum tersedia.</div>@endif
                <a class="text-link" href="{{ route('dashboard.mahasiswa.nilai') }}">Lihat nilai</a>
            </article>
            <article class="summary-card">
                <div class="summary-card-top"><span>Catatan kehadiran</span><span class="summary-icon teal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16"/><path d="m8 15 2.5 2.5L16 12"/></svg></span></div>
                @if ($attendanceSummary['percentage'] !== null)
                    <div class="summary-value">{{ number_format($attendanceSummary['percentage'], 1) }}%</div>
                    <div class="summary-note">Dari {{ $attendanceSummary['validCount'] }} pertemuan tercatat dengan status yang dikenali.</div>
                    @if ($attendanceSummary['unknownCount'] > 0)<div class="summary-note">{{ $attendanceSummary['unknownCount'] }} catatan dengan status yang belum dipetakan dikecualikan.</div>@endif
                @elseif ($attendanceSummary['validCount'] > 0)
                    <div class="summary-value is-empty">Belum cukup data</div>
                    <div class="summary-note">{{ $attendanceSummary['validCount'] }} pertemuan valid tercatat; minimal {{ $attendanceSummary['minimum'] }}.</div>
                @elseif ($attendanceSummary['unknownCount'] > 0)
                    <div class="summary-value is-empty">Status belum dikenali</div>
                    <div class="summary-note">{{ $attendanceSummary['unknownCount'] }} catatan dikecualikan karena statusnya belum dipetakan.</div>
                @else
                    <div class="summary-value is-empty">Belum tersedia</div><div class="summary-note">Data kehadiran belum tersedia.</div>
                @endif
                <div class="summary-note">Hadir/Terlambat: {{ $attendanceSummary['counts']['hadir'] + $attendanceSummary['counts']['terlambat'] }} · Izin: {{ $attendanceSummary['counts']['izin'] }} · Alpha: {{ $attendanceSummary['counts']['alpha'] }}</div>
                <a class="text-link" href="{{ route('dashboard.mahasiswa.kehadiran') }}">Lihat kehadiran</a>
            </article>
            <article class="summary-card">
                <div class="summary-card-top"><span>Mata kuliah aktif</span><span class="summary-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21zM4 4.5v14A2.5 2.5 0 0 1 6.5 16H20"/><path d="M8 6h8m-8 3h8"/></svg></span></div>
                @if ($semesterAktif && $jumlahMataKuliahAktif > 0)<div class="summary-value">{{ $jumlahMataKuliahAktif }}</div><div class="summary-note">Diambil pada {{ $semesterAktif->kode }}</div>@else<div class="summary-value is-empty">Belum tersedia</div><div class="summary-note">Tidak ada mata kuliah aktif tercatat.</div>@endif
                <a class="text-link" href="{{ route('dashboard.mahasiswa.krs') }}">Lihat KRS</a>
            </article>
            <article class="summary-card">
                <div class="summary-card-top"><span>Status risiko</span><span class="summary-icon amber"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3.8 18a1.4 1.4 0 0 0 1.2 2h14a1.4 1.4 0 0 0 1.2-2z"/><path d="M12 9v4m0 3h.01"/></svg></span></div>
                @if ($analisisTerbaru)<div class="summary-value"><span class="risk-badge {{ $riskClass }}">{{ str_replace(['_', '-'], ' ', $riskCategory) }}</span></div><div class="summary-note">Analisis tercatat {{ $analisisTerbaru->dianalisis_pada->format('d/m/Y') }}</div>@else<div class="summary-value is-empty">Belum tersedia</div><div class="summary-note">Belum ada analisis risiko tercatat.</div>@endif
                <a class="text-link" href="{{ route('dashboard.mahasiswa.analisis-risiko') }}">Lihat analisis</a>
            </article>
        </div>
    </section>

    <section class="student-section" aria-labelledby="grade-index-title">
        <div class="student-section-heading"><div><h2 id="grade-index-title">IPS dan IPK</h2><p>Perhitungan hanya memakai nilai final, berbobot SKS, sesuai skala nilai yang dikonfigurasi.</p></div></div>
        <article class="student-card">
            @if ($gradeSummary['ips'] !== null || $gradeSummary['ipk'] !== null)
                <p>IPS{{ $gradeSummary['ips_semester'] ? ' · '.$gradeSummary['ips_semester'] : '' }}: <strong>{{ $gradeSummary['ips'] !== null ? number_format($gradeSummary['ips'], 2) : 'Belum tersedia' }}</strong></p>
                <p>IPK: <strong>{{ $gradeSummary['ipk'] !== null ? number_format($gradeSummary['ipk'], 2) : 'Belum tersedia' }}</strong></p>
                <p class="card-subtitle">Berdasarkan {{ $gradeSummary['final_count'] }} catatan nilai final yang valid.</p>
            @else
                <div class="empty-state"><strong>IPS dan IPK belum tersedia</strong><span>Perhitungan muncul setelah nilai difinalisasi, memiliki SKS, dan sesuai skala nilai yang dikonfigurasi.</span></div>
            @endif
        </article>
    </section>

    <section class="student-section" aria-labelledby="trend-title">
        <div class="student-section-heading"><div><h2 id="trend-title">Perkembangan nilai</h2><p>Rata-rata nilai angka per semester dari catatan Anda.</p></div></div>
        <article class="student-card">
            @if ($grafik['tersedia'])
                <div class="chart-wrap"><canvas id="academicTrendChart" aria-label="Grafik rata-rata nilai per semester" role="img"></canvas></div>
                <script id="academicTrendData" type="application/json">{!! json_encode($grafik, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
            @else
                <div class="empty-state"><strong>Riwayat nilai belum mencukupi</strong><span>Grafik tersedia setelah nilai angka tercatat setidaknya pada dua semester.</span></div>
            @endif
        </article>
    </section>

    <section class="student-grid-main" aria-label="Tautan fitur akademik">
        <article class="student-card"><h3>Rekomendasi intervensi</h3><p class="card-subtitle">Buka daftar rekomendasi yang terkait dengan analisis Anda.</p><a class="text-link" href="{{ route('dashboard.mahasiswa.rekomendasi') }}">Buka rekomendasi</a></article>
        <article class="student-card"><h3>Tindak lanjut</h3><p class="card-subtitle">Lihat status tindak lanjut yang tercatat.</p><a class="text-link" href="{{ route('dashboard.mahasiswa.tindak-lanjut') }}">Buka tindak lanjut</a></article>
    </section>
@endsection
