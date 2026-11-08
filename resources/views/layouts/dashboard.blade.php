<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213d">
    <title>@yield('title', 'Dashboard') · UniNalar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/student-dashboard.css'])
    @if ((int) auth()->user()->level === \App\Models\User::ROLE_MAHASISWA)
        @vite(['resources/js/student-dashboard.js'])
    @endif
    @if ((int) auth()->user()->level === \App\Models\User::ROLE_ADMIN_AKADEMIK || (int) auth()->user()->level === \App\Models\User::ROLE_KAPRODI)
        @vite(['resources/css/admin-academic.css'])
    @endif
</head>
<body class="student-dashboard">
    @php
        $level = (int) auth()->user()->level;
        $roleLabel = \App\Models\User::ROLE_LABELS[$level] ?? 'Pengguna';
        $roleSlug = [1 => 'mahasiswa', 2 => 'dosen', 3 => 'admin-akademik', 4 => 'kaprodi'][$level] ?? 'pengguna';
        $navigation = config("dashboard.navigation.{$level}", []);
        $isStudent = $level === \App\Models\User::ROLE_MAHASISWA;
        $studentProfile = $isStudent ? ($mahasiswa ?? null) : null;
        $studentName = $studentProfile?->nama;
        $sidebarUserLabel = $studentName ?? auth()->user()->name;
        $isDemoAccount = $isStudent
            ? ((bool) ($isDemoProfile ?? false) || str_ends_with(strtolower((string) auth()->user()->email), '@demo.uninlar.test'))
            : (bool) ($isDemoAccount ?? str_ends_with(strtolower((string) auth()->user()->email), '@demo.uninlar.test'));
        $hour = now('Asia/Jakarta')->hour;
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    @endphp
    <a class="student-skip-link" href="#student-main">Lewati navigasi</a>
    <div class="student-layout" data-role="{{ $roleSlug }}">
        <x-dashboard.sidebar :role-slug="$roleSlug" :role-label="$roleLabel" :items="$navigation" :user-label="$sidebarUserLabel" :is-demo="$isDemoAccount" />
        <main class="student-content" id="student-main" tabindex="-1">
            <div class="student-main">
                @if ($isStudent)
                    <nav class="student-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard.mahasiswa') }}">Ringkasan akademik</a>
                        @unless (request()->routeIs('dashboard.mahasiswa'))<span aria-hidden="true">/</span><span>@yield('page-title')</span>@endunless
                    </nav>
                @endif
                <header class="student-topline">
                    <div class="student-greeting">
                        <small>{{ $isStudent ? $greeting.',' : $roleLabel }}</small>
                        <h1>@yield('page-title')</h1>
                        @hasSection('page-subtitle')<p>@yield('page-subtitle')</p>@endif
                    </div>
                    @if ($isStudent && isset($semesterAktif) && $semesterAktif)
                        <div class="semester-pill"><i aria-hidden="true"></i>{{ $semesterAktif->kode }}</div>
                    @endif
                </header>
                @if ($isStudent && !$mahasiswa && !request()->routeIs('dashboard.mahasiswa.profil'))
                    <div class="empty-state mb-4" role="status">
                        @if (!($tabelMahasiswaTersedia ?? false))
                            <strong>Data profil belum tersedia</strong><span>Tabel profil mahasiswa tidak ditemukan pada database aktif.</span>
                        @else
                            <strong>Profil Mahasiswa Belum Tersedia</strong><span>Profil akademik belum terhubung ke akun ini. Silakan hubungi Admin Akademik.</span>
                        @endif
                    </div>
                @elseif (!$isStudent && $isDemoAccount)
                    <div class="admin-notice" role="status"><strong>Akun demo.</strong> Identitas dan data yang terkait akun ini bukan data operasional.</div>
                @endif
                @if ($isStudent && !($skemaAkademikTersedia ?? false))
                    <div class="empty-state mb-4" role="status"><strong>Skema akademik belum lengkap</strong><span>Halaman ini hanya menampilkan data dari tabel operasional yang tersedia.</span></div>
                @endif
                @if ($level === \App\Models\User::ROLE_DOSEN)
                    <nav class="student-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard.dosen') }}">Dashboard Dosen</a>@unless (request()->routeIs('dashboard.dosen'))<span aria-hidden="true">/</span><span>@yield('page-title')</span>@endunless</nav>
                    @if (session('status'))<div class="student-card mb-3" role="status">{{ session('status') }}</div>@endif
                    @if ($errors->any())<div class="student-card mb-3" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                @endif
                @if ($level === \App\Models\User::ROLE_ADMIN_AKADEMIK)
                    <nav class="student-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard.admin-akademik') }}">Administrasi Akademik</a>@unless (request()->routeIs('dashboard.admin-akademik'))<span aria-hidden="true">/</span><span>@yield('page-title')</span>@endunless</nav>
                    @if (session('status'))<div class="admin-alert success" role="status">{{ session('status') }}</div>@endif
                    @if (session('error'))<div class="admin-alert error" role="alert">{{ session('error') }}</div>@endif
                    @if ($errors->any())<div class="admin-alert error" role="alert"><strong>Periksa kembali isian Anda.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @endif
                @if ($level === \App\Models\User::ROLE_KAPRODI)
                    <nav class="student-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard.owner') }}">Monitoring Akademik</a>@unless(request()->routeIs('dashboard.owner'))<span aria-hidden="true">/</span><span>@yield('page-title')</span>@endunless</nav>
                    @if ($errors->any())<div class="admin-alert error" role="alert"><strong>Periksa pilihan filter.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @endif
                @yield('content')
                @if ($isStudent)
                    <p class="student-footer-note">Informasi akademik berasal dari catatan database profil Anda. Skor risiko mengikuti analisis yang tercatat dan bukan keputusan otomatis.</p>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
