@extends('layouts.student')
@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Informasi profil mahasiswa yang tertaut ke akun Anda.')
@section('content')
    <article class="student-card">
        @if ($mahasiswa)
            <div class="student-profile-grid">
                <div class="profile-field"><span>Nama</span><strong>{{ $mahasiswa->nama }}</strong></div>
                <div class="profile-field"><span>NIM</span><strong>{{ $mahasiswa->nim }}</strong></div>
                <div class="profile-field"><span>Program studi</span><strong>{{ $mahasiswa->programStudi?->nama ?? 'Belum tersedia' }}</strong></div>
                <div class="profile-field"><span>Angkatan</span><strong>{{ $mahasiswa->angkatan }}</strong></div>
                <div class="profile-field"><span>Status</span><strong>{{ ucfirst($mahasiswa->status) }}</strong></div>
                <div class="profile-field"><span>Email profil</span><strong>{{ $mahasiswa->email ?: 'Belum tersedia' }}</strong></div>
                <div class="profile-field"><span>Nomor telepon</span><strong>{{ $mahasiswa->no_telepon ?: 'Belum tersedia' }}</strong></div>
            </div>
            <form class="student-form" method="POST" action="{{ route('dashboard.mahasiswa.profil.update') }}">
                @csrf
                @method('PUT')
                <h2>Informasi kontak</h2>
                @if (session('status'))<p class="form-success" role="status">{{ session('status') }}</p>@endif
                <div class="form-group">
                    <label for="email">Email profil</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $mahasiswa->email) }}" maxlength="255" autocomplete="email" aria-describedby="email-error">
                    @error('email')<span id="email-error" class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="no_telepon">Nomor telepon</label>
                    <input id="no_telepon" name="no_telepon" type="tel" value="{{ old('no_telepon', $mahasiswa->no_telepon) }}" maxlength="30" autocomplete="tel" aria-describedby="phone-error">
                    @error('no_telepon')<span id="phone-error" class="form-error">{{ $message }}</span>@enderror
                </div>
                <button class="btn btn-primary" type="submit">Simpan informasi kontak</button>
            </form>
        @else
            <div class="empty-state" role="status"><strong>Profil Mahasiswa Belum Tersedia</strong><span>Profil akademik belum terhubung ke akun ini. Silakan hubungi Admin Akademik.</span></div>
        @endif
    </article>
@endsection
