@extends('layouts.student')
@section('title', 'Tindak Lanjut')
@section('page-title', 'Tindak Lanjut')
@section('page-subtitle', 'Status tindak lanjut yang tercatat untuk rekomendasi Anda.')
@section('content')
    <article class="student-card">
        @if ($followUps->isNotEmpty())
            <table class="data-table"><thead><tr><th>Rekomendasi</th><th>Semester analisis</th><th>Status</th><th>Waktu</th></tr></thead><tbody>
                @foreach ($followUps as $item)
                    <tr><td><strong>{{ $item->rekomendasiIntervensi?->judul ?? 'Rekomendasi belum tersedia' }}</strong></td><td>{{ $item->rekomendasiIntervensi?->analisisRisiko?->semester?->kode ?? '—' }}</td><td><span class="status-chip">{{ $item->status }}</span></td><td>{{ $item->dilakukan_pada?->format('d/m/Y H:i') ?? 'Belum dijadwalkan' }}</td></tr>
                @endforeach
            </tbody></table>
        @else
            <div class="empty-state" role="status"><strong>Tindak lanjut belum tersedia</strong><span>Tindak lanjut akan tampil setelah dicatat untuk rekomendasi operasional Anda.</span></div>
        @endif
    </article>
@endsection
