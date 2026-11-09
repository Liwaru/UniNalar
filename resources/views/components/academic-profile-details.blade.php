@props(['fields'])
<section class="academic-profile-section" aria-labelledby="academic-profile-details">
    <h2 id="academic-profile-details">Informasi akademik</h2>
    <dl class="academic-profile-details">
        @foreach ($fields as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value ?: 'Data belum tersedia' }}</dd></div>@endforeach
    </dl>
    <p class="academic-profile-note">Data akademik dikelola oleh Admin Akademik.</p>
</section>
