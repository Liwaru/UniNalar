@extends('layouts.role-dashboard')
@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-subtitle', match ($pageTitle) {
    'Monitoring Mahasiswa' => 'Pantau status dan capaian akademik mahasiswa.',
    'Monitoring Dosen' => 'Lihat dosen dan kelas yang diampu.',
    'Monitoring Kelas' => 'Pantau kelas, peserta, dan nilai final.',
    'Monitoring KRS' => 'Pantau status KRS dan riwayat pengajuan mahasiswa.',
    'Monitoring Nilai Final' => 'Lihat nilai akademik yang telah difinalisasi.',
    'Monitoring Kehadiran' => 'Pantau catatan kehadiran perkuliahan.',
    'Analisis Risiko Tersimpan' => 'Tinjau hasil analisis akademik yang telah tersimpan.',
    'Rekomendasi Tersimpan' => 'Lihat rekomendasi berdasarkan hasil analisis.',
    'Monitoring Tindak Lanjut' => 'Pantau tindak lanjut yang telah dicatat.',
    'Laporan Akademik' => 'Bandingkan catatan akademik per program studi.',
    default => 'Rincian catatan akademik.',
})
@section('content')
<div class="owner-monitoring">
    @include('dashboard.owner.partials.filters')
    @if (!$available)
        <div class="admin-alert" role="status"><strong>Data belum tersedia.</strong> Hubungi pengelola akademik untuk informasi lebih lanjut.</div>
    @else
        <section class="owner-content-panel">@yield('monitoring')</section>
    @endif
@php($indexRoute = \Illuminate\Support\Str::beforeLast(request()->route()->getName(), '.').'.index')
@if (request()->routeIs('dashboard.owner.*.show') && \Illuminate\Support\Facades\Route::has($indexRoute))
<nav class="detail-actions" aria-label="Navigasi detail"><a class="academic-action is-secondary" href="{{ route($indexRoute, $filters ?? []) }}">Kembali ke daftar</a></nav>
@endif
</div>
@endsection
