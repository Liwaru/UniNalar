@extends('layouts.role-dashboard')
@section('title', $config['title'])
@section('page-title', $config['title'])
@php
    $resource = str($config['route'])->afterLast('.')->toString();
    $resourceLabel = ['dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'][$resource] ?? $config['title'];
    $description = match ($resource) {
        'mahasiswa' => 'Kelola identitas dan profil akademik mahasiswa.',
        'dosen' => 'Kelola profil dosen dan penugasan program studi.',
        'semester' => 'Kelola semester, jadwal akademik, dan periode KRS.',
        'kelas-kuliah' => 'Kelola kelas, dosen pengampu, dan kapasitas per semester.',
        'mata-kuliah' => 'Kelola mata kuliah dan beban SKS program studi.',
        default => 'Kelola program studi dan status penyelenggaraannya.',
    };
@endphp
@section('page-subtitle', $description)
@section('page-actions')
    <a class="admin-button" href="{{ route($config['route'].'.create') }}">Tambah {{ $resourceLabel }}</a>
@endsection
@section('content')
    <section class="admin-panel admin-management" aria-label="Daftar {{ $resourceLabel }}">

        <form class="admin-search admin-toolbar" method="GET" action="{{ route($config['route'].'.index') }}">
            <label class="admin-filter-field admin-filter-search" for="admin-search"><span>Cari {{ strtolower($resourceLabel) }}</span>
                <input id="admin-search" type="search" name="q" maxlength="255" value="{{ $search }}" placeholder="Cari {{ strtolower($config['label']) }}">
            </label>
            @include('dashboard.admin-akademik.partials.filters')
            <div class="admin-toolbar-footer">
                <p class="admin-result-count" role="status"><strong>{{ number_format($records->total(), 0, ',', '.') }}</strong> {{ strtolower($resourceLabel) }} ditemukan</p>
                <div class="filter-actions">
                    <x-filter-reset :href="route($config['route'].'.index')" :disabled="$search === '' && count(array_filter($filters ?? [], fn ($v) => $v !== null && $v !== '')) === 0" />
                    <button class="admin-button" type="submit">Terapkan</button>
                </div>
            </div>
        </form>

        <div class="admin-table-wrap" role="region" aria-label="Daftar data akademik" tabindex="0">
            <table class="admin-table">
                <thead><tr>@foreach ($config['columns'] as $field => $label)<th scope="col">{{ $label }}@if (str_starts_with($field, 'krs_')) (WIB)@endif</th>@endforeach<th scope="col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($config['columns'] as $field => $label)
                                @php
                                    $value = data_get($record, $field);
                                    $isDate = in_array($field, ['tanggal_mulai', 'tanggal_selesai', 'krs_mulai_pada', 'krs_selesai_pada'], true);
                                    if ($value instanceof \DateTimeInterface) $value = $value->format(str_starts_with($field, 'krs_') ? 'd/m/Y H:i' : 'd/m/Y');
                                    elseif (str_starts_with($field, 'krs_') && is_string($value) && $value !== '') $value = \Carbon\Carbon::parse($value, 'Asia/Jakarta')->format('d/m/Y H:i');
                                    if ($field === 'status' && is_string($value)) $value = ucfirst($value);
                                    if ($field === 'kapasitas' && $value === null) $value = 'Tanpa batas';
                                @endphp
                                <td @class(['academic-date' => $isDate, 'academic-name' => in_array($field, ['nama', 'mataKuliah.nama'], true), 'academic-code' => in_array($field, ['kode', 'nidn', 'nim', 'kode_kelas', 'ruang', 'semester.kode', 'mataKuliah.kode'], true), 'academic-person' => in_array($field, ['nama', 'dosen.nama'], true) && in_array($config['route'], ['dashboard.admin-akademik.dosen', 'dashboard.admin-akademik.mahasiswa', 'dashboard.admin-akademik.kelas-kuliah'], true)])>@if (in_array($field, ['aktif', 'status'], true))<span @class(['admin-state', 'is-active' => $value === true || $value === 'Aktif'])>{{ is_bool($value) ? ($value ? 'Aktif' : 'Nonaktif') : ($value ?? 'Belum tersedia') }}</span>@else{{ $value ?? 'Belum tersedia' }}@endif</td>
                            @endforeach
                            <td><x-admin.record-actions :config="$config" :record="$record" :compact="count($config['columns']) >= 6" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($config['columns']) + 1 }}"><div class="admin-empty"><strong>@if ($search !== '' || count(array_filter($filters ?? [], fn ($v) => $v !== null && $v !== '')) > 0)Tidak ada data sesuai pilihan.@else Data operasional belum tersedia.@endif</strong><span>Sesuaikan pencarian atau hapus filter untuk melihat data lainnya.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $records->links() }}</div>
    </section>
@endsection
