@extends('layouts.role-dashboard')
@section('title', 'Pengajuan KRS')
@section('page-title', 'Tinjau Pengajuan KRS')
@section('content')
    <section class="admin-panel">
        <p class="admin-intro">Periksa pengajuan terbaru atau telusuri riwayat semua versi. Keputusan hanya tersedia untuk versi yang sedang menunggu.</p>
        @if (!$available)
            <div class="admin-empty" role="status"><strong>Alur persetujuan belum tersedia.</strong><span>Hubungi pengelola sistem untuk memeriksa kesiapan pengajuan KRS.</span></div>
        @else
            <form class="admin-search" method="GET" action="{{ route('dashboard.admin-akademik.krs-pengajuan.index') }}">
                <label class="admin-filter-field" for="krs-search"><span>Nama atau NIM</span><input id="krs-search" name="q" type="search" maxlength="255" value="{{ $filters['q'] }}"></label>
                @include('dashboard.admin-akademik.partials.filters')
                <label class="admin-filter-field" for="krs-status"><span>Status</span><select id="krs-status" name="status">@foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua status'] as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="admin-filter-field" for="krs-scope"><span>Cakupan</span><select id="krs-scope" name="cakupan"><option value="terbaru" @selected($filters['cakupan'] === 'terbaru')>Versi terbaru per KRS</option><option value="semua_versi" @selected($filters['cakupan'] === 'semua_versi')>Semua versi</option></select></label>
                <button class="admin-button secondary" type="submit">Terapkan filter</button><a class="admin-clear" href="{{ route('dashboard.admin-akademik.krs-pengajuan.index') }}">Hapus filter</a>
            </form>
            <div class="admin-table-wrap" role="region" aria-label="Daftar pengajuan KRS" tabindex="0"><table class="admin-table"><thead><tr><th>Mahasiswa</th><th>Semester</th><th>Versi</th><th>Diajukan (WIB)</th><th>Ringkasan pengajuan</th><th>Keputusan</th></tr></thead><tbody>
                @forelse ($submissions as $submission)
                    <tr><td class="academic-identity">{{ $submission->krs->mahasiswa->nama }}<br><span class="academic-code">{{ $submission->krs->mahasiswa->nim }}</span></td><td>{{ $submission->krs->semester->kode }}</td><td>{{ $submission->versi }}</td><td>{{ $submission->diajukan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td><a href="{{ route('dashboard.admin-akademik.krs-pengajuan.show', $submission->id) }}">Detail pengajuan</a><ul class="admin-snapshot-list">@foreach (($submission->snapshot_kelas['kelas'] ?? []) as $class)<li>{{ $class['kode_mata_kuliah'] ?? '—' }} · {{ $class['nama_mata_kuliah'] ?? 'Nama tidak tersedia' }} · {{ $class['sks'] ?? '—' }} SKS</li>@endforeach</ul></td><td>@include('dashboard.admin-akademik.partials.decision')</td></tr>
                @empty<tr><td colspan="6"><div class="admin-empty"><strong>Tidak ada pengajuan sesuai pilihan.</strong><span>Ubah filter atau periksa kembali setelah mahasiswa mengajukan KRS.</span></div></td></tr>@endforelse
            </tbody></table></div>
            <div class="admin-pagination">{{ $submissions->links() }}</div>
        @endif
    </section>
@endsection
