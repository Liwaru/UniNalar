@extends('layouts.role-dashboard')
@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-subtitle', 'Pemantauan akademik berdasarkan catatan operasional.')
@section('content')
    @include('dashboard.owner.partials.filters')
    @if (!$available)
        <div class="admin-alert" role="status"><strong>Data belum tersedia.</strong> Tabel yang diperlukan untuk halaman ini belum lengkap.</div>
    @else
        @yield('monitoring')
    @endif
    <p class="admin-help">Halaman monitoring hanya untuk membaca data. Perubahan akademik dilakukan melalui peran yang berwenang.</p>
@php($indexRoute = \Illuminate\Support\Str::beforeLast(request()->route()->getName(), '.').'.index')
@if (request()->routeIs('dashboard.owner.*.show') && \Illuminate\Support\Facades\Route::has($indexRoute))
<nav class="detail-actions" aria-label="Navigasi detail"><a class="academic-action is-secondary" href="{{ route($indexRoute, $filters ?? []) }}">Kembali ke daftar</a></nav>
@endif
@endsection
