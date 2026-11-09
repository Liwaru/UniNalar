@extends('layouts.student')
@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Informasi profil mahasiswa yang tertaut ke akun Anda.')
@section('content')
    <article class="student-card academic-profile">
        @if ($mahasiswa)
            <x-academic-profile-header :name="$mahasiswa->nama" role="Mahasiswa"
                :identity="'NIM '.$mahasiswa->nim" :program="$mahasiswa->programStudi?->nama ?? 'Program studi belum tersedia'"
                :status="ucfirst($mahasiswa->status ?: 'Belum tersedia')" :active="$mahasiswa->status === 'aktif'"
                :meta="'Angkatan '.($mahasiswa->angkatan ?? 'belum tersedia')" />
            <x-academic-profile-details :fields="['Jenjang pendidikan' => $mahasiswa->programStudi?->jenjang, 'Kode program studi' => $mahasiswa->programStudi?->kode]" />
            <section class="academic-profile-section" aria-labelledby="academic-profile-contact">
                <h2 id="academic-profile-contact">Informasi kontak</h2>
                <p class="academic-profile-note" id="academic-profile-contact-help">Perbarui kontak yang terhubung dengan profil Anda. Perubahan ini tidak mengubah akun login atau data akademik.</p>
                <x-dashboard.validation-summary />
                <form class="student-form academic-profile-form" method="POST" action="{{ route('dashboard.mahasiswa.profil.update') }}" aria-describedby="academic-profile-contact-help">
                    @csrf
                    @method('PUT')
                    @if (session('status'))<p class="form-success" role="status">{{ session('status') }}</p>@endif
                    <div class="form-group">
                        <label for="email">Email profil</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $mahasiswa->email) }}" maxlength="255" autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')<span id="email-error" class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="no_telepon">Nomor telepon</label>
                        <input id="no_telepon" name="no_telepon" type="tel" value="{{ old('no_telepon', $mahasiswa->no_telepon) }}" maxlength="30" autocomplete="tel" @error('no_telepon') aria-invalid="true" aria-describedby="phone-error" @enderror>
                        @error('no_telepon')<span id="phone-error" class="form-error">{{ $message }}</span>@enderror
                    </div>
                    <button class="btn btn-primary" type="submit">Simpan informasi kontak</button>
                </form>
            </section>
        @else
            <div class="empty-state" role="status"><strong>Profil Mahasiswa Belum Tersedia</strong><span>Profil akademik belum terhubung ke akun ini. Silakan hubungi Admin Akademik.</span></div>
        @endif
    </article>
@endsection
