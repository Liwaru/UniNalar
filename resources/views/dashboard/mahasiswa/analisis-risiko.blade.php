@extends('layouts.student')
@section('title', 'Analisis Risiko')
@section('page-title', 'Analisis Risiko')
@section('page-subtitle', 'Riwayat analisis dan faktor penjelas yang tersimpan.')
@section('content')
    @forelse ($analyses as $analysis)
        <section class="student-section">
            <div class="student-section-heading"><div><h2>{{ $analysis->semester?->kode ?? 'Periode tidak dicantumkan' }}</h2><p>Dianalisis {{ $analysis->dianalisis_pada?->format('d/m/Y H:i') ?? 'waktu belum tersedia' }}</p></div><span class="risk-badge">{{ str_replace(['_', '-'], ' ', $analysis->kategori) }}</span></div>
            <article class="student-card">
                <p class="risk-score">Skor tercatat: {{ number_format((float) $analysis->skor, 4) }}</p>
                @if (is_array($analysis->ringkasan) && is_string($analysis->ringkasan['penjelasan'] ?? null) && trim($analysis->ringkasan['penjelasan']) !== '')<p class="risk-description">{{ $analysis->ringkasan['penjelasan'] }}</p>@elseif (is_string($analysis->ringkasan) && trim($analysis->ringkasan) !== '')<p class="risk-description">{{ $analysis->ringkasan }}</p>@endif
                @if ($analysis->faktorRisiko->isNotEmpty())
                    <div class="data-list">
                        @foreach ($analysis->faktorRisiko as $factor)
                            <div class="data-list-item"><div class="data-list-top"><strong>{{ $factor->nama }}</strong>@if ($factor->kontribusi !== null)<span class="status-chip">Kontribusi {{ number_format((float) $factor->kontribusi, 4) }}</span>@endif</div>@if ($factor->penjelasan)<p>{{ $factor->penjelasan }}</p>@else<p>Penjelasan faktor belum dicatat.</p>@endif</div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-3"><strong>Faktor penjelas belum tersedia</strong><span>Analisis ini belum memiliki catatan faktor.</span></div>
                @endif
            </article>
        </section>
    @empty
        <div class="empty-state" role="status"><strong>Analisis risiko belum tersedia</strong><span>Dashboard hanya menampilkan analisis yang sudah tersimpan. Skor tidak dihitung otomatis.</span></div>
    @endforelse
@endsection
