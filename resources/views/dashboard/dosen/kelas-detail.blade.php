@extends('layouts.role-dashboard')
@section('title', 'Detail Kelas')
@section('page-title', 'Detail Kelas')
@section('page-subtitle'){{ $class->mataKuliah->kode.' · '.$class->mataKuliah->nama }}@endsection
@section('content')
    @include('dashboard.dosen.partials.class-links')
    <article class="student-card">
        <h2 class="academic-card-title">{{ $class->mataKuliah->nama }}</h2>
        <dl class="student-profile-grid">
            @foreach (['Kode mata kuliah' => $class->mataKuliah->kode, 'SKS' => $class->mataKuliah->sks, 'Semester' => $class->semester->kode, 'Kelas' => $class->kode_kelas, 'Kapasitas' => $class->kapasitas ?? 'Tanpa batas', 'Peserta KRS disetujui' => $stats['participants'], 'Belum dinilai' => $stats['ungraded'] ?? 'Belum tersedia', 'Nilai draft' => $stats['draft'] ?? 'Belum tersedia', 'Nilai final' => $stats['final'] ?? 'Belum tersedia'] as $label => $value)
                <div class="profile-field"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p class="card-subtitle">Peserta resmi berasal dari KRS disetujui dan detail berstatus diambil. Pengajuan menunggu/ditolak tidak termasuk.</p>
    </article>
@endsection
