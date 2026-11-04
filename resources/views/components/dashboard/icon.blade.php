@props(['name'])

<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    @switch($name)
        @case('book')
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21zM4 5.5v13A2.5 2.5 0 0 1 6.5 16H20"/><path d="M8 7h8M8 10h8"/>
            @break
        @case('chart')
            <path d="M4 19V5m0 14h16M7 15l3-4 3 2 5-7"/>
            @break
        @case('calendar')
            <path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16"/><path d="m8 15 2.5 2.5L16 12"/>
            @break
        @case('alert')
            <path d="M12 3 3.8 18a1.4 1.4 0 0 0 1.2 2h14a1.4 1.4 0 0 0 1.2-2z"/><path d="M12 9v4m0 3h.01"/>
            @break
        @case('sparkle')
            <path d="M12 3 14.5 9.5 21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>
            @break
        @case('message')
            <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h8m-8 3h5"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('dashboard')
            <rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/>
            @break
        @case('building')
            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-4h4v4"/>
            @break
        @case('teacher')
            <circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-12h5m-2.5-2.5v5"/>
            @break
        @case('student')
            <path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5m4-2v7"/>
            @break
    @endswitch
</svg>
