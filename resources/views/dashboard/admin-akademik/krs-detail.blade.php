@extends('layouts.role-dashboard')
@section('title', 'Detail Pengajuan KRS')
@section('page-title', 'Detail Pengajuan KRS')
@section('page-subtitle'){{ $submission->krs->mahasiswa->nim }} · {{ $submission->krs->mahasiswa->nama }}@endsection
@section('content')
    <section class="admin-panel">
        <dl class="detail-fields">
            @foreach (['Semester' => $submission->krs->semester->kode, 'Program studi' => $submission->krs->mahasiswa->programStudi?->nama, 'Versi' => $submission->versi, 'Status versi' => ucfirst($submission->status), 'Status KRS saat ini' => ucfirst($submission->krs->status), 'Pengaju' => $submission->diajukanOleh?->name, 'Pengambil keputusan' => $submission->diputuskanOleh?->name, 'Diajukan (WIB)' => $submission->diajukan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i'), 'Diputuskan (WIB)' => $submission->diputuskan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i')] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?? 'Belum tersedia' }}</dd></div>@endforeach
        </dl>
        <h2 class="academic-card-title">Pilihan kelas saat diajukan</h2>
        <div class="admin-table-wrap" role="region" aria-label="Snapshot pengajuan" tabindex="0"><table class="admin-table"><thead><tr><th>Kode</th><th>Mata kuliah</th><th>Kelas</th><th>SKS</th></tr></thead><tbody>@foreach (($submission->snapshot_kelas['kelas'] ?? []) as $snapshot)<tr><td>{{ $snapshot['kode_mata_kuliah'] ?? '—' }}</td><td>{{ $snapshot['nama_mata_kuliah'] ?? '—' }}</td><td>{{ $snapshot['kode_kelas'] ?? '—' }}</td><td>{{ $snapshot['sks'] ?? '—' }}</td></tr>@endforeach</tbody></table></div>
        <p class="admin-help">Snapshot mempertahankan pilihan saat versi ini diajukan.</p>
        @include('dashboard.admin-akademik.partials.decision')
    </section>
    <section class="admin-panel"><h2 class="academic-card-title">Riwayat versi KRS</h2><div class="admin-table-wrap" role="region" aria-label="Riwayat versi KRS" tabindex="0"><table class="admin-table"><thead><tr><th>Versi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach ($versions as $version)<tr><td>{{ $version->versi }}</td><td>{{ ucfirst($version->status) }}</td><td><a href="{{ route('dashboard.admin-akademik.krs-pengajuan.show', $version->id) }}">Buka versi {{ $version->versi }}</a></td></tr>@endforeach</tbody></table></div>{{ $versions->links() }}<a class="text-link" href="{{ route('dashboard.admin-akademik.krs-pengajuan.index') }}">Kembali ke pengajuan</a></section>
@endsection
