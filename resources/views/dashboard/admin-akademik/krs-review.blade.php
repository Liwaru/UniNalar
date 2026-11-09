@extends('layouts.role-dashboard')
@section('title', 'Pengajuan KRS')
@section('page-title', 'Tinjau Pengajuan KRS')
@section('page-subtitle', 'Tinjau pengajuan terbaru atau telusuri riwayat semua versi KRS.')
@section('content')
    <section class="admin-panel admin-management">
        @if (!$available)
            <div class="admin-empty" role="status"><strong>Alur persetujuan belum tersedia.</strong><span>Hubungi pengelola sistem untuk memeriksa kesiapan pengajuan KRS.</span></div>
        @else
            <form class="admin-search admin-toolbar" method="GET" action="{{ route('dashboard.admin-akademik.krs-pengajuan.index') }}">
                <label class="admin-filter-field" for="krs-search"><span>Nama atau NIM</span><input id="krs-search" name="q" type="search" maxlength="255" value="{{ $filters['q'] }}"></label>
                @include('dashboard.admin-akademik.partials.filters')
                <label class="admin-filter-field" for="krs-status"><span>Status</span><select id="krs-status" name="status">@foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua status'] as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="admin-filter-field" for="krs-scope"><span>Cakupan</span><select id="krs-scope" name="cakupan"><option value="terbaru" @selected($filters['cakupan'] === 'terbaru')>Versi terbaru per KRS</option><option value="semua_versi" @selected($filters['cakupan'] === 'semua_versi')>Semua versi</option></select></label>
                <div class="admin-toolbar-footer">
                    <p class="admin-result-count" role="status"><strong>{{ number_format($submissions->total(), 0, ',', '.') }}</strong> pengajuan ditemukan · {{ $filters['cakupan'] === 'terbaru' ? 'Versi terbaru per KRS' : 'Semua versi' }}</p>
                    <div class="filter-actions"><x-filter-reset :href="route('dashboard.admin-akademik.krs-pengajuan.index')" /><button class="admin-button" type="submit">Terapkan</button></div>
                </div>
            </form>
            <p class="admin-workflow-note">Keputusan hanya tersedia untuk versi terbaru yang sedang menunggu.</p>
            <div class="admin-table-wrap" role="region" aria-label="Daftar pengajuan KRS" tabindex="0"><table class="admin-table"><thead><tr><th>Mahasiswa</th><th>Semester</th><th>Versi</th><th>Diajukan (WIB)</th><th>Ringkasan pengajuan</th><th>Keputusan</th></tr></thead><tbody>
                @forelse ($submissions as $submission)
                    <tr><td class="academic-identity"><strong>{{ $submission->krs->mahasiswa->nama }}</strong><br><span class="academic-code">{{ $submission->krs->mahasiswa->nim }}</span></td><td class="academic-code">{{ $submission->krs->semester->kode }}</td><td>{{ $submission->versi }}</td><td class="academic-date">{{ $submission->diajukan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td><a class="admin-row-action" href="{{ route('dashboard.admin-akademik.krs-pengajuan.show', $submission->id) }}">Detail pengajuan</a><ul class="admin-snapshot-list">@foreach (($submission->snapshot_kelas['kelas'] ?? []) as $class)<li>{{ $class['kode_mata_kuliah'] ?? '—' }} · {{ $class['nama_mata_kuliah'] ?? 'Nama tidak tersedia' }} · {{ $class['sks'] ?? '—' }} SKS</li>@endforeach</ul></td><td>@include('dashboard.admin-akademik.partials.decision')</td></tr>
                @empty<tr><td colspan="6"><div class="admin-empty"><strong>Tidak ada pengajuan sesuai pilihan.</strong><span>Ubah filter atau periksa kembali setelah mahasiswa mengajukan KRS.</span></div></td></tr>@endforelse
            </tbody></table></div>
            <div class="admin-pagination">{{ $submissions->links() }}</div>
        @endif
    </section>
@endsection
