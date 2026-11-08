@extends('layouts.role-dashboard')
@section('title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('page-title', ($record ? 'Ubah ' : 'Tambah ').$config['label'])
@section('content')
    <section class="admin-panel admin-form-panel">
        <p class="admin-intro">Isi data akademik berikut. Field bertanda * wajib diisi.</p>
        <form method="POST" action="{{ $record ? route($config['route'].'.update', $record) : route($config['route'].'.store') }}" class="admin-form">
            @csrf
            @if ($record) @method('PUT') @endif
            @foreach ($config['fields'] as $field => $definition)
                @php
                    $locked = $definition['locked'] ?? false;
                    $value = $locked ? $record?->{$field} : old($field, $record?->{$field});
                    if ($value instanceof \DateTimeInterface) $value = $value->format($definition['type'] === 'datetime-local' ? 'Y-m-d\\TH:i' : 'Y-m-d');
                    elseif ($definition['type'] === 'datetime-local' && is_string($value) && $value !== '') $value = str_replace(' ', 'T', substr($value, 0, 16));
                    $choices = $options[$field] ?? ($definition['choices'] ?? []);
                @endphp
                @if ($definition['type'] === 'checkbox')
                    <label class="admin-checkbox"><input type="checkbox" name="{{ $field }}" value="1" @checked(session()->hasOldInput() ? (bool) old($field, false) : (bool) ($value ?? true))><span>{{ $definition['label'] }}</span></label>
                @else
                    <div class="admin-field">
                        <label for="field-{{ $field }}">{{ $definition['label'] }}@if (!($definition['nullable'] ?? false)) <b aria-hidden="true">*</b>@endif</label>
                        @if ($definition['type'] === 'select')
                            @if ($locked)<input type="hidden" name="{{ $field }}" value="{{ $value }}">@endif
                            <select id="field-{{ $field }}" name="{{ $field }}" @disabled($locked) @required(!($definition['nullable'] ?? false)) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="help-{{ $field }}">
                                @if ($definition['nullable'] ?? false)<option value="">Tidak ditautkan</option>@else<option value="">Pilih {{ strtolower($definition['label']) }}</option>@endif
                                @foreach ($choices as $id => $label)<option value="{{ $id }}" @selected((string) $value === (string) $id)>{{ $label }}</option>@endforeach
                            </select>
                        @else
                            <input id="field-{{ $field }}" type="{{ $definition['type'] }}" name="{{ $field }}" value="{{ $value }}" @readonly($locked) @required(!($definition['nullable'] ?? false)) aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="help-{{ $field }}" @if (isset($definition['maxlength'])) maxlength="{{ $definition['maxlength'] }}" @endif @if ($definition['type'] === 'number') min="{{ $definition['min'] ?? ($field === 'angkatan' ? 1900 : 1) }}" max="{{ $definition['max'] ?? ($field === 'angkatan' ? 2200 : 255) }}" @endif>
                        @endif
                        <div id="help-{{ $field }}">@if (!empty($definition['help']))<small>{{ $definition['help'] }}</small>@endif @if ($definition['type'] === 'select' && empty($choices))<small>Pilihan belum tersedia. Periksa data induk atau akun terkait.</small>@endif @error($field)<span class="admin-field-error">{{ $message }}</span>@enderror</div>
                    </div>
                @endif
            @endforeach
            <div class="admin-form-actions"><button type="submit" class="admin-button">Simpan</button><a class="admin-button secondary" href="{{ route($config['route'].'.index') }}">Batal</a></div>
        </form>
    </section>
@endsection
