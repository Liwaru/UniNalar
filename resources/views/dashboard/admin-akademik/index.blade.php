@extends('layouts.role-dashboard')
@section('title', 'Admin Akademik')
@section('page-title', 'Administrasi Akademik')
@section('page-subtitle', 'Data akademik dan pengajuan KRS yang perlu ditinjau.')
@section('content')
    @if (!$available)
        <div class="admin-panel" role="status"><h2>Data akademik belum tersedia</h2><p>Hubungi pengelola sistem untuk memeriksa kesiapan data.</p></div>
    @else
        <section class="admin-panel" aria-labelledby="master-summary-title">
            <h2 id="master-summary-title" class="academic-card-title">Data akademik</h2>
            <dl class="admin-metrics">
                @foreach (['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'program-studi' => 'Program studi', 'semester' => 'Semester', 'mata-kuliah' => 'Mata kuliah', 'kelas-kuliah' => 'Kelas kuliah'] as $key => $label)
                    <div><dt>{{ $label }}</dt><dd><a href="{{ route('dashboard.admin-akademik.'.$key.'.index') }}">{{ $totals[$key] }}</a></dd></div>
                @endforeach
            </dl>
            <p class="admin-help">@if (app()->environment('local'))Data demonstrasi sintetis. @endif Jumlah mengikuti catatan akademik yang tersedia.</p>
        </section>
        <section class="admin-panel" aria-labelledby="krs-summary-title">
            <div class="admin-panel-heading"><h2 id="krs-summary-title" class="academic-card-title">Status KRS</h2><a class="admin-button secondary" href="{{ route('dashboard.admin-akademik.krs-pengajuan.index') }}">Tinjau pengajuan</a></div>
            <dl class="admin-metrics">
                @foreach (['draft' => 'Draft', 'menunggu' => 'Menunggu keputusan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $status => $label)
                    <div><dt>{{ $label }}</dt><dd>@if ($workflowAvailable && $status !== 'draft')<a href="{{ route('dashboard.admin-akademik.krs-pengajuan.index', ['status' => $status]) }}">{{ $krsStatuses[$status] ?? 0 }}</a>@else{{ $krsStatuses[$status] ?? 0 }}@endif</dd></div>
                @endforeach
            </dl>
            <p class="admin-help">Status KRS saat ini, satu catatan per mahasiswa dan semester. Draft belum diajukan. Riwayat semua versi tersedia pada daftar pengajuan.</p>
            @unless ($workflowAvailable)<p role="status">Persetujuan KRS belum dapat digunakan. Hubungi pengelola sistem.</p>@endunless
        </section>
    @endif
    <nav class="admin-module-links" aria-label="Pengelolaan akademik">
        @foreach (['program-studi' => 'Program Studi', 'semester' => 'Semester', 'mata-kuliah' => 'Mata Kuliah', 'kelas-kuliah' => 'Kelas Kuliah', 'dosen' => 'Data Dosen', 'mahasiswa' => 'Data Mahasiswa', 'krs-pengajuan' => 'Pengajuan KRS'] as $key => $label)
            <a href="{{ route('dashboard.admin-akademik.'.$key.'.index') }}">{{ $label }}</a>
        @endforeach
    </nav>
@endsection
