@extends('layouts.role-dashboard')
@section('title', 'Monitoring Akademik')
@section('page-title', 'Monitoring Akademik')
@section('page-subtitle', 'Ringkasan catatan operasional lintas program studi.')
@section('content')
    <form class="monitoring-filters student-card" method="GET" action="{{ route('dashboard.owner') }}">
        <label for="program-filter">Program studi<select id="program-filter" name="program_studi_id"><option value="">Semua program studi</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected((string) ($filters['program_studi_id'] ?? '') === (string) $program->id)>{{ $program->nama }}</option>@endforeach</select></label>
        <label for="semester-filter">Semester<select id="semester-filter" name="semester_id"><option value="">Semua semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->kode }}</option>@endforeach</select></label>
        <button type="submit">Terapkan filter</button>
    </form>
    @if ($errors->any())<div class="student-card" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if (! $available)
        <div class="empty-state" role="status"><strong>Schema akademik belum tersedia</strong><span>Monitoring memerlukan tabel akademik yang lengkap.</span></div>
    @elseif (! $stats)
        <div class="empty-state" role="status"><strong>Data operasional belum tersedia</strong><span>Belum ada profil mahasiswa operasional untuk pilihan program studi ini.</span></div>
    @else
        <div class="summary-grid">
            @foreach (['Mahasiswa operasional' => $stats['students'], 'Catatan KRS' => $stats['krs'], 'Rata-rata IPS terbaru' => $stats['ips'] !== null ? number_format($stats['ips'], 2) : 'Belum tersedia', 'Rata-rata IPK' => $stats['ipk'] !== null ? number_format($stats['ipk'], 2) : 'Belum tersedia'] as $label => $value)
                <article class="summary-card"><div class="summary-card-top"><span>{{ $label }}</span></div><div class="summary-value">{{ $value }}</div></article>
            @endforeach
        </div>
        <p class="card-subtitle">IPS terbaru dan IPK mengikuti seluruh riwayat nilai final tiap mahasiswa. Filter semester membatasi KRS, analisis, rekomendasi, dan tindak lanjut. Mahasiswa dengan nilai final valid: {{ $stats['graded_students'] }}.</p>
        <div class="student-grid-main">
            @foreach (['Status KRS' => $krsStatuses, 'Risiko dari analisis terbaru per mahasiswa' => $riskDistribution, 'Rekomendasi tercatat' => $recommendations, 'Tindak lanjut tercatat' => $followUps] as $title => $items)
                <article class="student-card"><h2 class="academic-card-title">{{ $title }}</h2>
                    @if ($items->isEmpty())<div class="empty-state"><strong>Data belum tersedia</strong><span>Ringkasan muncul setelah catatan operasional tersedia.</span></div>
                    @else<ul class="monitoring-counts">@foreach ($items as $status => $count)<li><span>{{ str_replace('_', ' ', $status) }}</span><strong>{{ $count }}</strong></li>@endforeach</ul>@endif
                </article>
            @endforeach
        </div>
        <p class="student-footer-note">Analisis dan rekomendasi berasal dari catatan tersimpan. Perhitungan risiko otomatis dan penjelasan AI belum tersedia.</p>
    @endif
@endsection
