@extends('layouts.role-dashboard')
@section('title', 'Detail Kelas Kuliah')
@section('page-title', 'Detail Kelas Kuliah')
@section('content')
    <article class="admin-panel">
        <h2>{{ $record->mataKuliah->nama }} · {{ $record->kode_kelas }}</h2>
        <dl class="student-profile-grid">
            @foreach (['Mata kuliah' => $record->mataKuliah->kode, 'Program studi' => $record->mataKuliah->programStudi->nama, 'Semester' => $record->semester->kode, 'Dosen pengampu' => $record->dosen?->nama ?? 'Belum ditugaskan', 'Kapasitas' => $record->kapasitas ?? 'Tanpa batas', 'Peserta dan pengajuan menunggu' => $enrollments, 'Ruang' => $record->ruang ?? 'Belum tersedia'] as $label => $value)
                <div class="profile-field"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p>Identitas dan pengampu kelas dikunci setelah kelas digunakan dalam KRS.</p>
        <a class="admin-button" href="{{ route('dashboard.admin-akademik.kelas-kuliah.edit', $record) }}">Ubah kelas</a>
        <a class="admin-button secondary" href="{{ route('dashboard.admin-akademik.kelas-kuliah.index') }}">Kembali ke daftar</a>
    </article>
@endsection
