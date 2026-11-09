@extends('layouts.role-dashboard')
@section('title', 'Rekap Kehadiran')
@section('page-title', 'Rekap Kehadiran')
@section('page-subtitle'){{ $class->mataKuliah->nama.' · '.$class->semester->kode.' · '.$class->kode_kelas }}@endsection
@section('content')
    @include('dashboard.dosen.partials.class-links')
    <p class="student-card">Rekap baca dari catatan tersimpan. Input dan koreksi kehadiran memerlukan aturan resmi; tidak ada persentase kelulusan yang diasumsikan.</p>
    <form class="monitoring-filters student-card" method="GET" action="{{ url()->current() }}">
        <label for="attendance-search">Nama atau NIM<input id="attendance-search" type="search" name="q" maxlength="255" value="{{ $filters['q'] ?? '' }}"></label>
        <label for="meeting-filter">Pertemuan<input id="meeting-filter" type="number" name="pertemuan_ke" min="1" max="65535" value="{{ $filters['pertemuan_ke'] ?? '' }}"></label>
        <div class="filter-actions"><button type="submit">Terapkan filter</button><x-filter-reset :href="url()->current()" />
    </div>
</form>
    <article class="student-card">
        @if (!$available)<div class="empty-state"><strong>Schema kehadiran belum tersedia</strong></div>
        @elseif ($records->isEmpty())<div class="empty-state"><strong>Catatan kehadiran belum tersedia</strong><span>Tidak ada catatan peserta resmi sesuai filter.</span></div>
        @else
        <p>Rekap seluruh hasil filter: @foreach ($statuses as $status => $count)<span class="status-chip">{{ $status }}: {{ $count }}</span> @endforeach</p>
        <div class="academic-table-wrap" role="region" aria-label="Kehadiran peserta" tabindex="0"><table class="data-table"><thead><tr><th>Mahasiswa</th><th>Pertemuan</th><th>Tanggal</th><th>Status tercatat</th></tr></thead><tbody>
        @foreach ($records as $record)<tr><td>{{ $record->krsDetail->krs->mahasiswa->nim }} · {{ $record->krsDetail->krs->mahasiswa->nama }}</td><td>{{ $record->pertemuan_ke }}</td><td>{{ $record->tanggal?->format('d/m/Y') ?? 'Belum tersedia' }}</td><td>{{ $record->status }}</td></tr>@endforeach
        </tbody></table></div>
        @endif
    </article>
    @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator){{ $records->links() }}@endif
@endsection
