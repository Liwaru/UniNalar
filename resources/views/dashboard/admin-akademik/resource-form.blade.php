@extends('layouts.role-dashboard')
@section('title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('page-title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('content')
    <section class="admin-panel admin-form-panel">
        <p class="admin-intro">Masukkan informasi dari sumber akademik yang sah. Form ini tidak membuat akun atau password.</p>
        @if ($errors->any())
            <div class="admin-alert error" role="alert"><strong>Periksa kembali isian berikut:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ $record ? route($config['route'].'.update', $record) : route($config['route'].'.store') }}" class="admin-form">
            @csrf
            @if ($record) @method('PUT') @endif
            @foreach ($config['fields'] as $field => $definition)
                @php
                    $value = old($field, $record?->{$field});
                    if ($value instanceof \DateTimeInterface) $value = $value->format($definition['type'] === 'datetime-local' ? 'Y-m-d\\TH:i' : 'Y-m-d');
                    elseif ($definition['type'] === 'datetime-local' && is_string($value) && $value !== '') $value = str_replace(' ', 'T', substr($value, 0, 16));
                    $choices = $options[$field] ?? ($definition['choices'] ?? []);
                @endphp
                @if ($definition['type'] === 'checkbox')
                    <label class="admin-checkbox"><input type="checkbox" name="{{ $field }}" value="1" @checked(session()->hasOldInput() ? (bool) old($field, false) : (bool) ($value ?? true))><span>{{ $definition['label'] }}</span></label>
                @else
                    <label class="admin-field" for="field-{{ $field }}"><span>{{ $definition['label'] }}@if (!($definition['nullable'] ?? false)) <b aria-hidden="true">*</b>@endif</span>
                        @if ($definition['type'] === 'select')
                            <select id="field-{{ $field }}" name="{{ $field }}" @required(!($definition['nullable'] ?? false))>
                                @if ($definition['nullable'] ?? false)<option value="">Tidak ditautkan</option>@endif
                                @foreach ($choices as $id => $label)<option value="{{ $id }}" @selected((string) $value === (string) $id)>{{ $label }}</option>@endforeach
                            </select>
                            @if (empty($choices))<small>Pilihan operasional belum tersedia. Tambahkan data induk atau akun role yang sesuai terlebih dahulu.</small>@endif
                        @else
                            <input id="field-{{ $field }}" type="{{ $definition['type'] }}" name="{{ $field }}" value="{{ $value }}" @required(!($definition['nullable'] ?? false)) @if ($definition['type'] === 'number') min="{{ $definition['min'] ?? ($field === 'angkatan' ? 1900 : 1) }}" max="{{ $definition['max'] ?? ($field === 'angkatan' ? 2200 : 255) }}" @endif>
                        @endif
                    </label>
                @endif
            @endforeach
            <div class="admin-form-actions"><button type="submit" class="admin-button">Simpan</button><a class="admin-button secondary" href="{{ route($config['route'].'.index') }}">Batal</a></div>
        </form>
    </section>
@endsection
