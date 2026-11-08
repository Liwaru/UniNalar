@extends('layouts.role-dashboard')
@section('title', 'Profil Dosen')
@section('page-title', 'Profil Dosen')
@section('page-subtitle', 'Identitas akademik dan informasi kontak profil Anda.')
@section('content')
    @include('dashboard.dosen.partials.state')
    @if ($lecturer)
        <article class="student-card">
            <dl class="student-profile-grid">
                @foreach (['Nama' => $lecturer->nama, 'NIDN' => $lecturer->nidn ?? 'Belum tersedia', 'Program studi' => $lecturer->programStudi?->nama ?? 'Belum tersedia', 'Status' => $lecturer->aktif ? 'Aktif' : 'Tidak aktif'] as $label => $value)<div class="profile-field"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
            </dl>
            <form class="student-form" method="POST" action="{{ route('dashboard.dosen.profil.update') }}">
                @csrf @method('PUT')<h2>Perbarui kontak</h2>
                <div class="form-group"><label for="lecturer-email">Email profil</label><input id="lecturer-email" name="email" type="email" maxlength="255" value="{{ old('email', $lecturer->email) }}">@error('email')<span class="form-error">{{ $message }}</span>@enderror</div>
                <div class="form-group"><label for="lecturer-phone">Nomor telepon</label><input id="lecturer-phone" name="no_telepon" maxlength="30" value="{{ old('no_telepon', $lecturer->no_telepon) }}">@error('no_telepon')<span class="form-error">{{ $message }}</span>@enderror</div>
                <p class="card-subtitle">Kontak profil tidak mengubah akun login, NIDN, program studi, atau kewenangan Anda.</p>
                <button class="btn" type="submit">Simpan kontak</button>
            </form>
        </article>
    @endif
@endsection
