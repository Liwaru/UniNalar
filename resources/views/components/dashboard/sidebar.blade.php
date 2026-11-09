@props(['roleSlug', 'roleLabel', 'items', 'userLabel', 'isDemo' => false])

@php
    $displayUserLabel = $roleSlug === 'mahasiswa' && $isDemo
        ? 'Mahasiswa'
        : ($isDemo ? 'Akun demo' : $userLabel);
    $avatarInitial = $roleSlug === 'mahasiswa' && $isDemo
        ? 'M'
        : mb_substr($displayUserLabel, 0, 1);
@endphp

<aside class="student-sidebar" data-role="{{ $roleSlug }}" aria-label="Navigasi {{ $roleLabel }}" tabindex="0">
    <a class="student-brand" href="{{ route('dashboard') }}" aria-label="UniNalar, Dashboard sesuai role">
        <img src="{{ asset('images/UniNalar Icon.png') }}" alt="" width="35" height="35">
        <div><strong>UniNalar</strong><span>Ruang {{ $roleLabel }}</span></div>
    </a>
    <nav class="student-nav" aria-label="Menu utama">
        <p class="student-nav-label">Menu utama</p>
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                <x-dashboard.icon :name="$item['icon']" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
    <div class="student-sidebar-footer">
        <div class="student-identity">
            <div class="student-avatar" aria-hidden="true">{{ mb_strtoupper($avatarInitial) }}</div>
            <div class="min-w-0"><strong title="{{ $displayUserLabel }}">{{ $displayUserLabel }}</strong><span>{{ $roleLabel }}</span></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="student-logout" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>Keluar</button>
        </form>
    </div>
</aside>
