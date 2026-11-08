@extends('layouts.role-dashboard')
@section('title', $config['title'])
@section('page-title', $config['title'])
@section('content')
    <section class="admin-panel">
        <div class="admin-panel-heading">
            <div><p class="admin-intro">Kelola {{ strtolower($config['label']) }} operasional. Data demo tidak ditampilkan dalam daftar ini.</p></div>
            <a class="admin-button" href="{{ route($config['route'].'.create') }}">Tambah {{ $config['label'] }}</a>
        </div>

        <form class="admin-search" method="GET" action="{{ route($config['route'].'.index') }}">
            <label for="admin-search">Cari data</label>
            <input id="admin-search" type="search" name="q" maxlength="255" value="{{ $search }}" placeholder="Cari {{ strtolower($config['label']) }}">
            @include('dashboard.admin-akademik.partials.filters')
            <button class="admin-button secondary" type="submit">Cari</button>
            @if ($search !== '' || count(array_filter($filters ?? [], fn ($v) => $v !== null && $v !== '')) > 0)<a class="admin-clear" href="{{ route($config['route'].'.index') }}">Hapus pencarian</a>@endif
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
                                <td>{{ is_bool($value) ? ($value ? 'Aktif' : 'Nonaktif') : ($value ?? 'Belum tersedia') }}</td>
                            @endforeach
                            <td><div class="admin-row-actions">
                                @if ($config['show'] ?? false)<a href="{{ route($config['route'].'.show', $record) }}">Detail</a>@endif
                                <a href="{{ route($config['route'].'.edit', $record) }}">Ubah</a>
                                <form method="POST" action="{{ route($config['route'].'.destroy', $record) }}" onsubmit="return confirm('Hapus data ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="admin-delete">Hapus</button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($config['columns']) + 1 }}"><div class="admin-empty"><strong>Data operasional belum tersedia.</strong><span>Tambahkan data dari sumber resmi atau sesuaikan pencarian.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $records->links() }}</div>
    </section>
@endsection
