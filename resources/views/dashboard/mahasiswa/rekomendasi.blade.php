@extends('layouts.student')
@section('title', 'Rekomendasi Intervensi')
@section('page-title', 'Rekomendasi Intervensi')
@section('page-subtitle', 'Riwayat rekomendasi berdasarkan semester dan hasil analisis Anda.')
@section('content')
    @include('dashboard.mahasiswa.partials.semester-filter')
    <div class="recommendation-toolbar">
        <p>Rekomendasi tersimpan, dikelompokkan menurut periode akademik.</p>
        <a class="text-link" href="{{ route('dashboard.mahasiswa.analisis-risiko', array_filter(['semester_id' => $filters['semester_id'] ?? null])) }}">Kembali ke Analisis Risiko</a>
    </div>
    @forelse ($recommendationGroups as $semesterKey => $group)
        @php
            $semester = $group->first()->semester;
            $totals = $recommendationTotals->get($semesterKey);
        @endphp
        <section class="student-card recommendation-semester" data-recommendation-semester="{{ $semesterKey }}" aria-labelledby="recommendation-period-{{ $semesterKey }}">
            <header class="recommendation-period-header">
                <div><h2 id="recommendation-period-{{ $semesterKey }}">{{ $semester?->kode ?? 'Semester belum dicatat' }}</h2><p>{{ $totals->recommendation_count }} rekomendasi · {{ $totals->analysis_count }} analisis</p></div>
                <span class="recommendation-history-label">Riwayat tersimpan</span>
            </header>
            @if ($group->count() < $totals->analysis_count)<p class="data-meta">Ditampilkan {{ $group->count() }} dari {{ $totals->analysis_count }} analisis semester ini pada halaman ini. Gunakan navigasi halaman untuk hasil lainnya.</p>@endif
            @foreach ($group as $analysis)
                @php
                    $transport = data_get($analysis->ringkasan, 'transport');
                    $source = data_get($analysis->ringkasan, 'sumber');
                    $isAi = $source === 'groq_deskriptif';
                    $sourceLabel = $transport === 'mock' ? 'Simulasi pengujian' : ($transport === 'live' ? 'Groq live' : ($isAi ? 'Groq · transport belum dicatat' : (is_string($source) ? $source : 'Sumber belum dicatat')));
                @endphp
                <section class="recommendation-analysis" data-recommendation-analysis="{{ $analysis->id }}" aria-labelledby="recommendation-analysis-{{ $analysis->id }}">
                    <header class="recommendation-analysis-header">
                        <h3 id="recommendation-analysis-{{ $analysis->id }}">{{ $analysis->dianalisis_pada?->timezone('Asia/Jakarta')->format('d/m/Y · H:i \W\I\B') ?? 'Tanggal analisis belum dicatat' }}</h3>
                        <p>{{ $sourceLabel }} · Analisis #{{ $analysis->id }} · {{ $analysis->rekomendasiIntervensi->count() }} rekomendasi</p>
                        <x-ai-provenance :summary="$analysis->ringkasan" />
                    </header>
                    <ol class="recommendation-items">
                        @foreach ($analysis->rekomendasiIntervensi as $item)
                            @php
                                $description = trim((string) $item->deskripsi) !== '' ? $item->deskripsi : $item->faktorRisiko?->penjelasan;
                                $normalize = fn ($text) => mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $text)));
                                $hasDescription = trim((string) $description) !== '' && $normalize($description) !== $normalize($item->judul);
                                $hasStatus = trim((string) $item->status) !== '' && mb_strtolower($item->status) !== 'direkomendasikan';
                            @endphp
                            <li data-recommendation-id="{{ $item->id }}"><div class="recommendation-item-copy">
                                <h4>{{ $item->judul }}</h4>
                                @if ($hasDescription)<p>{{ $description }}</p>@endif
                                @if (!$hasDescription && $item->faktor_risiko_id && !$item->faktorRisiko)<p class="data-meta">Alasan rekomendasi belum dicatat.</p>@endif
                                @if ($hasStatus || $item->tenggat_pada)<p class="data-meta">@if ($hasStatus)Status tercatat: {{ $item->status }}@endif @if ($item->tenggat_pada){{ $hasStatus ? '· ' : '' }}Tenggat {{ $item->tenggat_pada->format('d/m/Y') }}@endif</p>@endif
                            </div></li>
                        @endforeach
                    </ol>
                </section>
            @endforeach
        </section>
    @empty
        <div class="student-card empty-state" role="status"><strong>Rekomendasi belum tersedia</strong><span>{{ !empty($filters['semester_id']) ? 'Belum ada rekomendasi tersimpan untuk semester yang dipilih. Coba pilih Semua semester.' : 'Rekomendasi akan tampil jika tercatat pada analisis operasional Anda.' }}</span></div>
    @endforelse
    @if ($analysisPages && $analysisPages->hasPages())<div class="student-pagination recommendation-pagination"><p class="data-meta">Halaman {{ $analysisPages->currentPage() }} dari {{ $analysisPages->lastPage() }} · {{ $analysisPages->total() }} analisis dengan rekomendasi. Setiap analisis ditampilkan lengkap.</p>{{ $analysisPages->links() }}</div>@endif
@endsection
