@extends('layouts.role-dashboard')
@section('title', 'Admin Akademik')
@section('page-title', 'Administrasi Akademik')
@section('content')
    <p class="admin-intro">Kelola referensi akademik dan profil yang dimasukkan dari sumber resmi. Profil dan referensi demo dipisahkan dari daftar operasional.</p>
    <div class="admin-module-grid">
        @foreach ([
            ['Program Studi', 'Kelola program studi, jenjang, dan status operasional.', 'dashboard.admin-akademik.program-studi.index'],
            ['Semester', 'Kelola periode dan rentang tanggal semester.', 'dashboard.admin-akademik.semester.index'],
            ['Mata Kuliah', 'Kelola katalog mata kuliah dan keterkaitannya dengan program studi.', 'dashboard.admin-akademik.mata-kuliah.index'],
            ['Kelas Kuliah', 'Kelola kelas per semester, dosen pengampu, dan kapasitas KRS.', 'dashboard.admin-akademik.kelas-kuliah.index'],
            ['Data Dosen', 'Kelola profil dosen dan tautan ke akun dosen yang sudah ada.', 'dashboard.admin-akademik.dosen.index'],
            ['Data Mahasiswa', 'Kelola profil mahasiswa dan tautan ke akun mahasiswa yang sudah ada.', 'dashboard.admin-akademik.mahasiswa.index'],
            ['Pengajuan KRS', 'Tinjau snapshot pengajuan dan simpan keputusan Admin Akademik.', 'dashboard.admin-akademik.krs-pengajuan.index'],
        ] as [$title, $description, $routeName])
            <a class="admin-module-card" href="{{ route($routeName) }}">
                <span class="admin-module-eyebrow">Data operasional</span>
                <strong>{{ $title }}</strong>
                <span>{{ $description }}</span>
            </a>
        @endforeach
    </div>
@endsection
