@extends('layouts.role-dashboard')
@section('title', 'Dashboard '.$role)
@section('page-title', 'Dashboard '.$role)
@section('content')
    <p>Selamat datang, {{ auth()->user()->name }}.</p>
    <p>Role: {{ $role }}</p>
    <div role="status"><strong>Ringkasan operasional belum tersedia.</strong><p>Halaman fitur untuk role ini belum tersedia pada aplikasi saat ini.</p></div>
    @if ($role === \App\Models\User::ROLE_LABELS[\App\Models\User::ROLE_DOSEN])
        <p><a href="{{ route('dashboard.dosen.nilai') }}">Buka finalisasi nilai kelas</a></p>
    @endif
@endsection
