@extends('layouts.student')
@section('title', 'Kehadiran')
@section('page-title', 'Kehadiran')
@section('page-subtitle', 'Catatan kehadiran yang terhubung ke KRS Anda.')
@section('content')
    <article class="student-card">
        @if ($kehadiran->isNotEmpty())
            <table class="data-table"><thead><tr><th>Tanggal</th><th>Semester</th><th>Mata kuliah</th><th>Pertemuan</th><th>Status</th></tr></thead><tbody>
                @foreach ($kehadiran as $record)
                    @php($detail = $record->krsDetail)
                    @php($course = $detail?->kelasKuliah?->mataKuliah)
                    <tr><td>{{ $record->tanggal?->format('d/m/Y') ?? 'Belum tersedia' }}</td><td>{{ $detail?->krs?->semester?->kode ?? '—' }}</td><td><strong>{{ $course?->nama ?? 'Data mata kuliah belum tersedia' }}</strong></td><td>{{ $record->pertemuan_ke }}</td><td><span class="status-chip">{{ $record->status }}</span></td></tr>
                @endforeach
            </tbody></table>
        @else
            <div class="empty-state" role="status"><strong>Data kehadiran belum tersedia</strong><span>Catatan kehadiran akan muncul setelah dicatat pada pertemuan mata kuliah Anda.</span></div>
        @endif
    </article>
@endsection
