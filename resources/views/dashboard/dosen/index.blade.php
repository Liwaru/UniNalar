@extends('layouts.role-dashboard')
@section('title', 'Dashboard Dosen')
@section('page-title', 'Dashboard Dosen')
@section('page-subtitle', 'Ringkasan kelas yang diampu dan proses penilaian.')
@section('content')
    @if ($lecturer)<p class="student-card"><strong>{{ $lecturer->nama }}</strong> · {{ $lecturer->nidn ?? 'NIDN belum tersedia' }}@if ($lecturer->nidn === \App\Services\DevelopmentDemoAccounts::SYNTHETIC_NIDN) <span class="text-muted">(identitas demonstrasi)</span>@endif</p>
    @include('dashboard.dosen.partials.filters', ['dashboardMode' => true])@endif
    @if (! $available)
        <div class="empty-state" role="status"><strong>Schema akademik belum tersedia</strong><span>Ringkasan ditampilkan setelah tabel pendukung tersedia.</span></div>
    @elseif (! $lecturer)
        <div class="empty-state" role="status"><strong>Profil dosen belum tertaut</strong><span>Hubungi Admin Akademik untuk menghubungkan profil operasional.</span></div>
    @elseif (! $stats)
        <div class="empty-state" role="status"><strong>Kelas operasional belum tersedia</strong><span>Belum ada kelas yang ditugaskan kepada Anda.</span></div>
    @else
        <div class="summary-grid">
            @foreach (['Kelas diampu' => $stats['classes'], 'Mahasiswa dengan KRS disetujui' => $stats['students'], 'Nilai belum final' => $stats['pending'] ?? 'Belum tersedia', 'Nilai final' => $stats['final'] ?? 'Belum tersedia', 'Peserta kelas belum dinilai' => $stats['ungraded'] ?? 'Belum tersedia', 'Catatan kehadiran' => $stats['attendance'] ?? 'Belum tersedia'] as $label => $value)
                <article class="summary-card"><div class="summary-card-top"><span>{{ $label }}</span></div><div class="summary-value">{{ $value }}</div></article>
            @endforeach
        </div>
        <p class="card-subtitle">Jumlah mahasiswa menghitung identitas unik. Status nilai menghitung peserta per kelas; nilai belum final mencakup draft dan peserta yang belum dinilai.</p>
        <a class="text-link" href="{{ route('dashboard.dosen.kelas.index', ['semester_id' => $filters['semester_id'] ?? null]) }}">Lihat seluruh kelas</a>
        <article class="student-card"><h2 class="academic-card-title">Kelas terbaru</h2><ul>@foreach ($classes as $class)<li><a class="text-link" href="{{ route('dashboard.dosen.kelas.show', $class->id) }}">{{ $class->mataKuliah->nama }} · {{ $class->kode_kelas }} · {{ $class->semester->kode }}</a></li>@endforeach</ul><a class="text-link" href="{{ route('dashboard.dosen.nilai', ['semester_id' => $filters['semester_id'] ?? null]) }}">Kelola nilai kelas</a></article>
    @endif
@endsection
