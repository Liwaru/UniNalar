@extends('layouts.student')
@section('title', 'Kehadiran')
@section('page-title', 'Kehadiran')
@section('page-subtitle', 'Catatan kehadiran yang terhubung ke KRS Anda.')
@section('content')
    @include('dashboard.mahasiswa.partials.semester-filter')
    <article class="student-card mb-3">
        <h2 class="academic-card-title">Ringkasan kehadiran</h2>
        <p>{{ $attendanceSummary['percentage'] !== null ? number_format($attendanceSummary['percentage'], 1).'%' : 'Belum cukup data untuk persentase' }}</p>
        <p>{{ $attendanceSummary['validCount'] }} pertemuan valid · {{ $attendanceSummary['unknownCount'] }} status belum dikenali. Minimal {{ $attendanceSummary['minimum'] }} catatan valid untuk menampilkan persentase.</p>
        <p class="card-subtitle">Berdasarkan seluruh hasil filter dari KRS disetujui dan kelas yang diambil, termasuk halaman berikutnya. Status belum dikenali dikecualikan.</p>
    </article>
    <article class="student-card">
        @if ($kehadiran->isNotEmpty())
            <div class="academic-table-wrap" role="region" aria-label="Tabel akademik" tabindex="0"><table class="data-table"><thead><tr><th>Tanggal</th><th>Semester</th><th>Mata kuliah</th><th>Pertemuan</th><th>Status</th></tr></thead><tbody>
                @foreach ($kehadiran as $record)
                    @php($detail = $record->krsDetail)
                    @php($course = $detail?->kelasKuliah?->mataKuliah)
                    <tr><td>{{ $record->tanggal?->format('d/m/Y') ?? 'Belum tersedia' }}</td><td>{{ $detail?->krs?->semester?->kode ?? '—' }}</td><td><strong>{{ $course?->nama ?? 'Data mata kuliah belum tersedia' }}</strong></td><td>{{ $record->pertemuan_ke }}</td><td><span class="status-chip">{{ $record->status }}</span></td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="empty-state" role="status"><strong>Data kehadiran belum tersedia</strong><span>Catatan kehadiran akan muncul setelah dicatat pada pertemuan mata kuliah Anda.</span></div>
        @endif
    </article>
    @if ($kehadiran instanceof \Illuminate\Contracts\Pagination\Paginator)<div class="student-pagination">{{ $kehadiran->links() }}</div>@endif
@endsection
