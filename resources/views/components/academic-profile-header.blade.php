@props(['name', 'role', 'identity', 'program', 'status', 'active' => false, 'meta' => null])
@php
    $initials = collect(preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY))->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp
<header class="academic-profile-identity">
    <span class="academic-profile-avatar" aria-hidden="true">{{ $initials }}</span>
    <div class="academic-profile-name">
        <p class="academic-profile-role">{{ $role }}</p>
        <h2>{{ $name }}</h2>
        <p class="academic-profile-subtitle"><span>{{ $identity }}</span><span>{{ $program }}</span></p>
        <div class="academic-profile-meta"><span @class(['academic-profile-status', 'is-active' => $active])>Status akademik: {{ $status }}</span>@if ($meta)<span>{{ $meta }}</span>@endif</div>
    </div>
</header>
