@extends('layouts.role-dashboard')
@section('title', $config['title'])
@section('page-title', $config['title'])
@section('content')
    <section class="admin-panel">
        <div class="admin-panel-heading admin-resource-heading">
            <a class="admin-button" href="{{ route($config['route'].'.create') }}">Tambah {{ $config['label'] }}</a>
        </div>

        <form class="admin-search" method="GET" action="{{ route($config['route'].'.index') }}">
            <label class="admin-filter-field admin-filter-search" for="admin-search"><span>Cari data</span>
                <input id="admin-search" type="search" name="q" maxlength="255" value="{{ $search }}" placeholder="Cari {{ strtolower($config['label']) }}">
            </label>
            @include('dashboard.admin-akademik.partials.filters')
            <div class="filter-actions"><button class="admin-button" type="submit">Cari</button>
            @if ($search !== '' || count(array_filter($filters ?? [], fn ($v) => $v !== null && $v !== '')) > 0)<x-filter-reset :href="route($config['route'].'.index')" />@endif
        </div>
</form>

        <div class="admin-table-wrap" role="region" aria-label="Daftar data akademik" tabindex="0">
            <table class="admin-table">
                <thead><tr>@foreach ($config['columns'] as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($config['columns'] as $field => $label)
                                @php
                                    $value = data_get($record, $field);
                                    if ($value instanceof \DateTimeInterface) $value = $value->format('d/m/Y');
                                    if ($field === 'kapasitas' && $value === null) $value = 'Tanpa batas';
                                @endphp
                                <td @class(['academic-code' => in_array($field, ['kode', 'nidn', 'nim', 'kode_kelas', 'ruang', 'semester.kode', 'mataKuliah.kode'], true), 'academic-person' => in_array($field, ['nama', 'dosen.nama'], true) && in_array($config['route'], ['dashboard.admin-akademik.dosen', 'dashboard.admin-akademik.mahasiswa', 'dashboard.admin-akademik.kelas-kuliah'], true)])>{{ is_bool($value) ? ($value ? 'Aktif' : 'Nonaktif') : ($value ?? 'Belum tersedia') }}</td>
                            @endforeach
                            <td><div class="admin-row-actions">
                                @if ($config['show'] ?? false)<a class="admin-row-action" href="{{ route($config['route'].'.show', $record) }}">Detail</a>@endif
                                <a class="admin-row-action is-edit" href="{{ route($config['route'].'.edit', $record) }}">Ubah</a>
                                <form method="POST" action="{{ route($config['route'].'.destroy', $record) }}" data-confirm-form inert>
                                    @csrf @method('DELETE')
                                    <x-confirm-action class="admin-row-action is-delete" :title="'Hapus '.$config['label'].'?'" description="Tindakan ini tidak dapat dibatalkan. Data yang masih digunakan dalam catatan akademik lain tidak dapat dihapus." :record="$record->nama ?? $record->kode ?? $record->kode_kelas" label="Hapus" tone="danger" />
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($config['columns']) + 1 }}"><div class="admin-empty"><strong>Data operasional belum tersedia.</strong><span>Tambahkan data atau sesuaikan pencarian.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $records->links() }}</div>
    </section>
@endsection
