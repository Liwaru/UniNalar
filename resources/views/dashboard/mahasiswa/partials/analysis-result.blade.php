@php
    $isDescriptive = data_get($analysis->ringkasan, 'sumber') === 'groq_deskriptif';
    $snapshot = $isDescriptive && is_array(data_get($analysis->ringkasan, 'indikator')) ? $analysis->ringkasan['indikator'] : [];
    $metrics = [
        ['IPS semester', 'ips_semester', 2, 'Skala 0–4'],
        ['IPK kumulatif', 'ipk_kumulatif', 2, 'Saat analisis dibuat'],
        ['SKS disetujui', 'sks_disetujui', 0, 'SKS'],
        ['Mata kuliah', 'mata_kuliah_disetujui', 0, 'Disetujui'],
        ['Nilai final', 'jumlah_nilai_final', 0, 'Catatan nilai'],
        ['Rata-rata nilai', 'rata_rata_nilai_final', 2, 'Skala 0–100'],
    ];
    $explanation = is_array($analysis->ringkasan) ? ($analysis->ringkasan['penjelasan'] ?? null) : $analysis->ringkasan;
    $attendance = $snapshot['kehadiran_tercatat'] ?? null;
@endphp
<article class="student-card analysis-result" aria-labelledby="analysis-title-{{ $analysis->id }}">
    <header class="analysis-result-header">
        <div>
            <p class="analysis-overline">Hasil tersimpan</p>
            <h2 id="analysis-title-{{ $analysis->id }}">{{ $analysis->semester?->kode ?? 'Periode tidak dicantumkan' }}</h2>
            <p class="data-meta">Dianalisis {{ $analysis->dianalisis_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i \W\I\B') ?? 'waktu belum tersedia' }}</p>
        </div>
        <span class="risk-badge {{ $analysis->kategori ? '' : 'is-unclassified' }}">{{ $analysis->kategori ? str_replace(['_', '-'], ' ', $analysis->kategori) : 'Deskriptif · belum diklasifikasikan' }}</span>
    </header>

    @if ($isDescriptive)
        <section class="analysis-block" aria-labelledby="snapshot-title-{{ $analysis->id }}">
            <h3 id="snapshot-title-{{ $analysis->id }}">Indikator yang diperhatikan <span class="analysis-time-label">Saat analisis</span></h3>
            <p class="data-meta">Snapshot catatan akademik yang digunakan saat hasil ini dibuat, bukan angka dari teks AI.</p>
            <dl class="analysis-metrics">
                @foreach ($metrics as [$label, $key, $decimals, $unit])
                    @php($value = $snapshot[$key] ?? null)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd @class(['is-unavailable' => !is_numeric($value)])>{{ is_numeric($value) ? number_format((float) $value, $decimals, ',', '.') : 'Data belum tersedia' }}</dd>
                        <span>{{ $unit }}</span>
                    </div>
                @endforeach
            </dl>
            @if (is_array($attendance) && count($attendance))
                <div class="analysis-attendance">
                    <strong>Kehadiran tercatat saat analisis</strong>
                    <dl>@foreach (['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'alpha' => 'Alpha'] as $key => $label)<div><dt>{{ $label }}</dt><dd>{{ is_numeric($attendance[$key] ?? null) ? number_format((float) $attendance[$key], 0, ',', '.') : 'Data belum tersedia' }}</dd></div>@endforeach</dl>
                    <p class="data-meta">Jumlah catatan per status, bukan persentase kehadiran. Catatan yang belum diisi tidak dihitung sebagai absen.</p>
                </div>
            @else
                <p class="data-meta">Kehadiran: Data belum tersedia pada snapshot ini.</p>
            @endif
        </section>
    @endif

    <section class="analysis-block">
        <h3>Ringkasan analisis</h3>
        @if (is_string($explanation) && trim($explanation) !== '')
            <p class="risk-description analysis-explanation">{{ $explanation }}</p>
        @else
            <p class="data-meta">Penjelasan belum tersedia.</p>
        @endif
        <x-ai-provenance :summary="$analysis->ringkasan" />
    </section>

    @if ($isDescriptive)
        <section class="analysis-block">
            <h3>Rekomendasi belajar</h3>
            <ol class="analysis-recommendations">
                @forelse ($analysis->rekomendasiIntervensi as $recommendation)
                    <li><p>{{ $recommendation->judul }}</p>@if ($recommendation->deskripsi && $recommendation->deskripsi !== $recommendation->judul)<p class="data-meta">{{ $recommendation->deskripsi }}</p>@endif</li>
                @empty
                    <li>Rekomendasi belum tersedia.</li>
                @endforelse
            </ol>
        </section>
    @endif

    <section class="analysis-block analysis-limitations">
        <h3>Keterbatasan interpretasi</h3>
        <p>{{ $analysis->skor === null ? 'Skor resmi belum ditetapkan.' : 'Skor tercatat: '.number_format((float) $analysis->skor, 4) }}</p>
        @if ($isDescriptive)<p>Penjelasan AI tidak menggantikan konsultasi dosen. Klasifikasi risiko memerlukan kebijakan akademik; bobot dan kontribusi faktor tidak ditetapkan pada analisis deskriptif.</p>@endif
        @if ($analysis->faktorRisiko->isNotEmpty())
            <div class="data-list">@foreach ($analysis->faktorRisiko as $factor)<div class="data-list-item"><strong>{{ $factor->nama }}</strong>@if ($factor->kontribusi !== null)<span class="status-chip">Kontribusi {{ number_format((float) $factor->kontribusi, 4) }}</span>@endif<p>{{ $factor->penjelasan ?? 'Penjelasan faktor belum dicatat.' }}</p></div>@endforeach</div>
        @elseif (!$isDescriptive)<p>Faktor penjelas belum tersedia.</p>@endif
    </section>
    <details class="analysis-source"><summary>Detail sumber analisis</summary><p class="data-meta">Sumber: {{ $isDescriptive ? 'Penjelasan Groq berdasarkan indikator agregat' : (is_string(data_get($analysis->ringkasan, 'sumber')) ? data_get($analysis->ringkasan, 'sumber') : 'Belum dicatat') }} · Model/metode: {{ $analysis->versi_model ?? 'Belum dicatat' }}</p></details>
</article>
