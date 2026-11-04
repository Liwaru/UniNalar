@extends('layouts.role-dashboard')
@section('title', $config['title'])
@section('page-title', $config['title'])
@section('content')
    <section class="admin-panel">
        <div class="admin-panel-heading">
            <div><p class="admin-intro">Kelola {{ strtolower($config['label']) }} operasional. Data demo tidak ditampilkan dalam daftar ini.</p></div>
            <a class="admin-button" href="{{ route($config['route'].'.create') }}">Tambah {{ $config['label'] }}</a>
        </div>

        @if (session('status'))<div class="admin-alert success" role="status">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="admin-alert error" role="alert">{{ session('error') }}</div>@endif

        <form class="admin-search" method="GET" action="{{ route($config['route'].'.index') }}">
            <label for="admin-search">Cari data</label>
            <input id="admin-search" type="search" name="q" value="{{ $search }}" placeholder="Cari {{ strtolower($config['label']) }}">
            <button class="admin-button secondary" type="submit">Cari</button>
            @if ($search !== '')<a class="admin-clear" href="{{ route($config['route'].'.index') }}">Hapus pencarian</a>@endif
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr>@foreach ($config['columns'] as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($config['columns'] as $field => $label)
                                @php($value = data_get($record, $field))
                                <td>{{ is_bool($value) ? ($value ? 'Aktif' : 'Nonaktif') : ($value ?? 'Belum tersedia') }}</td>
                            @endforeach
                            <td><div class="admin-row-actions">
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
