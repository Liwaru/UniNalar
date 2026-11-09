@extends('layouts.role-dashboard')
@section('title', 'Monitoring Akademik')
@section('page-title', 'Monitoring Akademik')
@section('page-subtitle', 'Ringkasan akademik lintas program studi.')
@section('content')
<div class="owner-monitoring">
@include('dashboard.owner.partials.filters')
@if(!$available)<div class="admin-alert" role="status">Data akademik belum tersedia. Hubungi pengelola akademik untuk informasi lebih lanjut.</div>
@else
<section class="admin-panel owner-summary"><h2>Data akademik</h2><dl class="admin-metrics">
@foreach(['Mahasiswa' => $totals['students'], 'Dosen' => $totals['lecturers'], 'Program studi' => $totals['programs'], 'Mata kuliah' => $metrics['courses'], 'Kelas kuliah' => $metrics['classes'], 'Peserta kelas resmi' => $metrics['official']] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
</dl><p class="owner-data-note">Kelas dan peserta mengikuti semester pilihan. Peserta adalah keikutsertaan kelas, bukan mahasiswa unik.</p><details class="owner-explanation"><summary>Cakupan data akademik</summary><p>Total profil, program, dan mata kuliah mengikuti program studi termasuk yang tidak aktif; tidak dibatasi semester. Program kelas mengikuti mata kuliah, sedangkan program peserta mengikuti mahasiswa. Data demo dikecualikan.</p></details></section>
@if(!$stats)<p class="admin-alert" role="status">Data operasional belum tersedia. Belum ada profil mahasiswa untuk pilihan program studi ini.</p>@endif
<section class="admin-panel owner-summary"><h2>Capaian akademik</h2><dl class="admin-metrics">
@foreach([($filters['semester_id'] ?? null) ? 'Rata-rata IPS semester pilihan' : 'Rata-rata IPS terbaru' => isset($stats['ips']) ? number_format($stats['ips'], 2) : 'Belum tersedia', 'Rata-rata IPK' => isset($stats['ipk']) ? number_format($stats['ipk'], 2) : 'Belum tersedia', 'Mahasiswa dengan nilai final valid' => $stats['graded_students'] ?? 0, 'Nilai final tercatat' => $metrics['final'] ?? 'Belum tersedia', 'Catatan kehadiran' => $metrics['attendance'] ?? 'Belum tersedia'] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
</dl><p class="owner-data-note">IPS mengikuti semester pilihan atau semester terbaru dengan nilai final valid. IPK mencakup seluruh riwayat.</p><details class="owner-explanation"><summary>Cara membaca capaian</summary><p>IPK memakai percobaan mata kuliah terakhir. Mahasiswa tanpa nilai valid tidak dihitung sebagai nol. Kehadiran yang belum dicatat tidak dihitung sebagai absen.</p></details></section>
@if($moduleAvailable['Status KRS'])<x-owner.distribution :items="$krsStatuses" title="Status KRS" unit="catatan KRS" kind="krs" note="Status KRS saat ini, sesuai program studi dan semester pilihan." />@endif
<section class="admin-panel owner-operational"><h2>Status operasional tersimpan</h2>
<div class="owner-operational-grid">
@foreach(['Risiko dari analisis terbaru per mahasiswa' => $riskDistribution, 'Rekomendasi tercatat' => $recommendations, 'Tindak lanjut tercatat' => $followUps] as $label => $items)
<section><h3>{{ $label }}</h3>@if(!$moduleAvailable[$label])<p class="owner-data-note">Data belum tersedia.</p>@elseif($items->isEmpty())<p class="owner-data-note">Belum ada catatan untuk pilihan ini.</p>@else<dl class="owner-metric-rows">@foreach($items as $status => $count)<div><dt><x-owner.status :value="$status" /></dt><dd>{{ $count }}</dd></div>@endforeach</dl>@endif</section>
@endforeach
</div><p class="owner-data-note">Analisis dan rekomendasi berasal dari hasil tersimpan, tanpa analisis baru. Belum diklasifikasikan bukan berarti risiko rendah.</p></section>
@endif
</div>
@endsection
