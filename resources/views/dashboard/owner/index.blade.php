@extends('layouts.role-dashboard')
@section('title', 'Monitoring Akademik')
@section('page-title', 'Monitoring Akademik')
@section('page-subtitle', 'Ringkasan catatan operasional lintas program studi.')
@section('content')
@include('dashboard.owner.partials.filters')
@if(!$available)<div class="admin-alert" role="status">Schema akademik belum tersedia. Monitoring memerlukan tabel akademik yang lengkap.</div>
@else
<section class="admin-panel"><h2>Data akademik</h2><dl class="admin-metrics">
@foreach(['Total mahasiswa' => $totals['students'], 'Total dosen' => $totals['lecturers'], 'Total program studi' => $totals['programs'], 'Mata kuliah' => $metrics['courses'], 'Kelas kuliah' => $metrics['classes'], 'Peserta kelas resmi' => $metrics['official']] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
</dl><p class="admin-help">Total profil, program, dan mata kuliah mengikuti program studi termasuk yang tidak aktif; tidak dibatasi semester. Kelas dan peserta mengikuti semester; program kelas mengikuti mata kuliah, sedangkan program peserta mengikuti mahasiswa. Peserta adalah jumlah keikutsertaan kelas, bukan mahasiswa unik. Data demo dikecualikan.</p></section>
@if(!$stats)<div class="admin-alert" role="status">Data operasional belum tersedia. Belum ada profil mahasiswa operasional untuk pilihan program studi ini.</div>@endif
<section class="admin-panel"><h2>Nilai dan catatan kehadiran</h2><dl class="admin-metrics">
@foreach([($filters['semester_id'] ?? null) ? 'Rata-rata IPS semester pilihan' : 'Rata-rata IPS terbaru' => isset($stats['ips']) ? number_format($stats['ips'], 2) : 'Belum tersedia', 'Rata-rata IPK' => isset($stats['ipk']) ? number_format($stats['ipk'], 2) : 'Belum tersedia', 'Mahasiswa dengan nilai final valid' => $stats['graded_students'] ?? 0, 'Nilai final tercatat' => $metrics['final'] ?? 'Belum tersedia', 'Catatan kehadiran' => $metrics['attendance'] ?? 'Belum tersedia'] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
</dl><p class="admin-help">IPS memakai semester pilihan, atau semester terbaru yang memiliki nilai final valid ketika semua semester dipilih. IPK selalu mengikuti seluruh riwayat nilai final dengan percobaan mata kuliah terakhir. Mahasiswa tanpa nilai valid tidak dihitung sebagai nol. Kehadiran yang belum dicatat tidak dihitung sebagai absen.</p></section>
<section class="admin-panel"><h2>Status operasional</h2>
@foreach(['Status KRS' => $krsStatuses, 'Risiko dari analisis terbaru per mahasiswa' => $riskDistribution, 'Rekomendasi tercatat' => $recommendations, 'Tindak lanjut tercatat' => $followUps] as $label => $items)
<h3 class="academic-card-title">{{ $label }}</h3>@if(!$moduleAvailable[$label])<p class="admin-help">Data belum dapat ditampilkan karena tabel pendukung belum lengkap.</p>@elseif($items->isEmpty())<p class="admin-help">Belum ada catatan {{ strtolower($label) }} untuk pilihan filter ini.</p>@else<dl class="admin-related-counts">@foreach($items as $status => $count)<div><dt>{{ str_replace('_', ' ', $status) }}</dt><dd>{{ $count }}</dd></div>@endforeach</dl>@endif
@endforeach
<p class="admin-help">Analisis dan rekomendasi menampilkan hasil tersimpan; tidak ada perhitungan risiko otomatis pada halaman ini.</p></section>
@endif
@endsection
