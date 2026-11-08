@extends('layouts.student')
@section('title', 'Nilai Akademik')
@section('page-title', 'Nilai Akademik')
@section('page-subtitle', 'Nilai yang tercatat untuk mata kuliah Anda.')
@section('content')
    @include('dashboard.mahasiswa.partials.semester-filter')
    @include('dashboard.mahasiswa.partials.grade-summary')
    <article class="student-card">
        @if ($nilai->isNotEmpty())
            <div class="academic-table-wrap" role="region" aria-label="Tabel akademik" tabindex="0"><table class="data-table"><thead><tr><th>Semester</th><th>Mata kuliah</th><th>Kode</th><th>Nilai angka</th><th>Huruf</th><th>Mutu</th><th>Status</th></tr></thead><tbody>
                @foreach ($nilai as $record)
                    @php($detail = $record->krsDetail)
                    @php($course = $detail?->kelasKuliah?->mataKuliah)
                    <tr><td>{{ $detail?->krs?->semester?->kode ?? '—' }}</td><td><strong>{{ $course?->nama ?? 'Data mata kuliah belum tersedia' }}</strong></td><td>{{ $course?->kode ?? '—' }}</td><td>{{ $record->nilai_angka !== null ? number_format((float) $record->nilai_angka, 2) : 'Belum tersedia' }}</td><td>{{ $record->nilai_huruf ?? 'Belum tersedia' }}</td><td>{{ $record->nilai_mutu !== null ? number_format((float) $record->nilai_mutu, 2) : 'Belum tersedia' }}</td><td>{{ $finalizationAvailable ? ($record->final_at ? 'Final' : 'Belum final') : 'Status finalisasi belum tersedia' }}</td></tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="empty-state" role="status"><strong>Data nilai belum tersedia</strong><span>Nilai akademik akan muncul setelah dosen atau pengelola mencatat hasil penilaian.</span></div>
        @endif
    </article>
    @if ($nilai instanceof \Illuminate\Contracts\Pagination\Paginator)<div class="student-pagination">{{ $nilai->links() }}</div>@endif
@endsection
