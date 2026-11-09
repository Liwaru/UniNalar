@extends('layouts.role-dashboard')
@section('title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('page-title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('content')
    <section class="admin-panel admin-form-panel">
        <p class="admin-intro">Isi data akademik berikut. Field bertanda * wajib diisi.</p>
        <form method="POST" action="{{ $record ? route($config['route'].'.update', $record) : route($config['route'].'.store') }}" class="admin-form is-sectioned">
            @csrf
            @if ($record) @method('PUT') @endif
            @php
                $fieldGroups = $config['route'] === 'dashboard.admin-akademik.semester'
                    ? ['Identitas semester' => ['kode', 'tahun_ajaran', 'periode'], 'Jadwal akademik' => ['tanggal_mulai', 'tanggal_selesai'], 'Periode KRS (WIB)' => ['krs_mulai_pada', 'krs_selesai_pada'], 'Status' => ['aktif']]
                    : ['Informasi '.strtolower($config['label']) => array_diff(array_keys($config['fields']), ['aktif']), 'Status' => array_intersect(array_keys($config['fields']), ['aktif'])];
            @endphp
            @foreach ($fieldGroups as $group => $fields)
                @if (count($fields))
                    <section class="academic-form-section" aria-labelledby="form-group-{{ $loop->index }}">
                        <h2 id="form-group-{{ $loop->index }}">{{ $group }}</h2>
                        <div class="admin-form-grid">
                            @foreach ($fields as $field)
                                @php($definition = $config['fields'][$field])
                                @include('dashboard.admin-akademik.partials.form-field')
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
            <div class="admin-form-actions"><button type="submit" class="admin-button">Simpan</button><a class="admin-button secondary" href="{{ route($config['route'].'.index') }}">Batal</a></div>
        </form>
    </section>
@endsection
