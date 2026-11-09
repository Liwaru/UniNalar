@extends('layouts.role-dashboard')
@section('title', 'Profil Dosen')
@section('page-title', 'Profil Dosen')
@section('page-subtitle', 'Identitas akademik dan informasi kontak profil Anda.')
@section('content')
    @include('dashboard.dosen.partials.state')
    @if ($lecturer)
        <article class="student-card academic-profile">
            <x-academic-profile-header :name="$lecturer->nama" role="Dosen"
                :identity="'NIDN '.($lecturer->nidn ?: 'belum tersedia').($lecturer->nidn === \App\Services\DevelopmentDemoAccounts::SYNTHETIC_NIDN ? ' (demonstrasi)' : '')" :program="$lecturer->programStudi?->nama ?? 'Program studi belum tersedia'"
                :status="$lecturer->aktif ? 'Aktif' : 'Tidak aktif'" :active="(bool) $lecturer->aktif" />
            <x-academic-profile-details :fields="['Jenjang pendidikan' => $lecturer->programStudi?->jenjang, 'Kode program studi' => $lecturer->programStudi?->kode]" />
            <section class="academic-profile-section" aria-labelledby="academic-profile-contact">
                <h2 id="academic-profile-contact">Informasi kontak</h2>
                <p class="academic-profile-note" id="academic-profile-contact-help">Perbarui kontak yang terhubung dengan profil Anda. Perubahan ini tidak mengubah akun login atau data akademik.</p>
                <form class="student-form academic-profile-form" method="POST" action="{{ route('dashboard.dosen.profil.update') }}" aria-describedby="academic-profile-contact-help">
                    @csrf @method('PUT')
                    <div class="form-group"><label for="lecturer-email">Email profil</label><input id="lecturer-email" @error('email') aria-invalid="true" aria-describedby="lecturer-email-error" @enderror name="email" type="email" maxlength="255" autocomplete="email" value="{{ old('email', $lecturer->email) }}">@error('email')<span id="lecturer-email-error" class="form-error">{{ $message }}</span>@enderror</div>
                    <div class="form-group"><label for="lecturer-phone">Nomor telepon</label><input id="lecturer-phone" @error('no_telepon') aria-invalid="true" aria-describedby="lecturer-phone-error" @enderror name="no_telepon" type="tel" maxlength="30" autocomplete="tel" value="{{ old('no_telepon', $lecturer->no_telepon) }}">@error('no_telepon')<span id="lecturer-phone-error" class="form-error">{{ $message }}</span>@enderror</div>
                    <button class="btn" type="submit">Simpan informasi kontak</button>
                </form>
            </section>
        </article>
    @endif
@endsection
