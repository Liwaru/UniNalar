@extends('layouts.student')
@section('title', 'Rekomendasi Intervensi')
@section('page-title', 'Rekomendasi Intervensi')
@section('page-subtitle', 'Rekomendasi yang tercatat pada analisis risiko Anda.')
@section('content')
    <article class="student-card">
        @if ($recommendations->isNotEmpty())
            <div class="data-list">
                @foreach ($recommendations as $item)
                    <div class="data-list-item"><div class="data-list-top"><strong>{{ $item->judul }}</strong><span class="status-chip">{{ $item->status }}</span></div>
                        @if ($item->deskripsi)<p>{{ $item->deskripsi }}</p>@elseif ($item->faktorRisiko?->penjelasan)<p>{{ $item->faktorRisiko->penjelasan }}</p>@else<p>Alasan rekomendasi belum dicatat.</p>@endif
                        <div class="data-meta">Prioritas {{ $item->prioritas }}@if ($item->analisisRisiko?->semester) · {{ $item->analisisRisiko->semester->kode }}@endif @if ($item->tenggat_pada) · Tenggat {{ $item->tenggat_pada->format('d/m/Y') }}@endif</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state" role="status"><strong>Rekomendasi belum tersedia</strong><span>Rekomendasi akan tampil jika tercatat pada analisis operasional Anda.</span></div>
        @endif
    </article>
@endsection
