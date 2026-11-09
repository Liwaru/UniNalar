@props(['fields'])
<dl {{ $attributes->class(['detail-fields']) }}>
    @foreach ($fields as $label => $value)
        <div><dt>{{ $label }}</dt><dd>@if (is_bool($value))<span @class(['academic-profile-status', 'is-active' => $value])>{{ $value ? 'Aktif' : 'Nonaktif' }}</span>@else{{ $value ?? 'Belum tersedia' }}@endif</dd></div>
    @endforeach
</dl>
