@extends('layouts.role-dashboard')
@section('title', 'Peserta Kelas')
@section('page-title', 'Peserta Kelas')
@section('page-subtitle'){{ $class->mataKuliah->nama.' · '.$class->semester->kode.' · '.$class->kode_kelas }}@endsection
@section('content')
    @include('dashboard.dosen.partials.class-links')
    @include('dashboard.dosen.partials.filters', ['participantMode' => true])
    <article class="student-card">
        @if (!$gradeAvailable)<p role="status">Status penilaian belum tersedia sampai schema finalisasi lengkap.</p>@endif
        @if ($participants->isNotEmpty())
        <div class="academic-table-wrap" role="region" aria-label="Peserta resmi" tabindex="0"><table class="data-table"><thead><tr><th>NIM</th><th>Nama</th><th>Program studi</th><th>Status KRS</th><th>Status nilai</th></tr></thead><tbody>
        @foreach ($participants as $detail)<tr><td>{{ $detail->krs->mahasiswa->nim }}</td><td>{{ $detail->krs->mahasiswa->nama }}</td><td>{{ $detail->krs->mahasiswa->programStudi?->nama ?? 'Belum tersedia' }}</td><td>Disetujui</td><td>{{ !$gradeAvailable ? 'Belum tersedia' : ($detail->nilai?->final_at ? 'Final' : ($detail->nilai ? 'Draft' : 'Belum dinilai')) }}</td></tr>@endforeach
        </tbody></table></div>
        @else<div class="empty-state"><strong>Peserta belum tersedia</strong><span>Belum ada peserta resmi atau tidak ada hasil sesuai filter.</span></div>@endif
    </article>
    {{ $participants->links() }}
@endsection
