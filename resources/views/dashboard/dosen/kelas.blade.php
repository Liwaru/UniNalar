@extends('layouts.role-dashboard')
@section('title', 'Kelas Diampu')
@section('page-title', 'Kelas Diampu')
@section('page-subtitle', 'Kelas operasional yang ditugaskan kepada profil dosen Anda.')
@section('content')
    @include('dashboard.dosen.partials.state')
    @if ($lecturer && $available)
        @include('dashboard.dosen.partials.filters')
        <article class="student-card">
            @if ($classes->isNotEmpty())
                <div class="academic-table-wrap" role="region" aria-label="Kelas diampu" tabindex="0"><table class="data-table"><thead><tr><th>Mata kuliah</th><th>Semester / kelas</th><th>Peserta resmi</th><th>Kapasitas</th><th>Aksi</th></tr></thead><tbody>
                @foreach ($classes as $class)<tr><td>{{ $class->mataKuliah->kode }} · {{ $class->mataKuliah->nama }}</td><td>{{ $class->semester->kode }} · {{ $class->kode_kelas }}</td><td>{{ $class->approved_count }}</td><td>{{ $class->kapasitas ?? 'Tanpa batas' }}</td><td><a class="text-link" href="{{ route('dashboard.dosen.kelas.show', $class->id) }}">Detail kelas</a></td></tr>@endforeach
                </tbody></table></div>
            @else<div class="empty-state" role="status"><strong>Kelas tidak ditemukan</strong><span>Belum ada penugasan atau tidak ada hasil sesuai filter.</span></div>@endif
        </article>
        {{ $classes->links() }}
    @endif
@endsection
