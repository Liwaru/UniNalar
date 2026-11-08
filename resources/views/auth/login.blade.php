<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213d">
    <meta name="description" content="Masuk ke UniNalar untuk mengakses analisis akademik dan pendampingan mahasiswa.">
    <title>Masuk · UniNalar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/login.css', 'resources/js/login.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-brand-panel" aria-labelledby="brand-heading">
            <a class="login-brand" href="{{ route('login') }}" aria-label="UniNalar">
                <img src="{{ asset('images/UniNalar Sidebar.png') }}" alt="UniNalar" width="220" height="74">
            </a>

            <div class="brand-copy">
                <span class="brand-eyebrow"><i></i> LAYANAN AKADEMIK UNINALAR</span>
                <h1 id="brand-heading">Informasi akademik. <span>Terhubung dalam satu layanan.</span></h1>
                <p>Akses KRS, nilai, dan catatan pendampingan sesuai peran Anda.</p>
            </div>

            <div class="academic-visual" aria-hidden="true">
                <div class="visual-orbit orbit-outer"></div>
                <div class="visual-orbit orbit-inner"></div>
                <div class="visual-node node-one"></div>
                <div class="visual-node node-two"></div>
                <div class="visual-node node-three"></div>
                <div class="visual-core"><span>U</span><i></i></div>
                <div class="visual-caption"><span>DATA</span><b></b><span>INSIGHT</span><b></b><span>PENDAMPINGAN</span></div>
            </div>

            <p class="brand-footnote">Informasi akademik, dipahami dengan lebih baik.</p>
        </section>

        <section class="login-form-panel" aria-labelledby="login-heading">
            <div class="login-form-wrap">
                <span class="form-kicker">SELAMAT DATANG KEMBALI</span>
                <h2 id="login-heading">Masuk ke UniNalar</h2>
                <p class="form-description">Gunakan akun akademik yang diberikan kepada Anda.</p>

                @if ($errors->any())
                    <div class="form-alert" id="login-error" role="alert" aria-live="polite">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="8"/><path d="M10 6v4m0 3h.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="login-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            placeholder="Masukkan username"
                            @error('username') aria-invalid="true" aria-describedby="login-error" @enderror
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-field">
                        <label for="password">Password</label>
                        <div class="password-control">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                @error('password') aria-invalid="true" aria-describedby="login-error" @enderror
                                required
                            >
                            <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-controls="password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/>
                                    <circle cx="12" cy="12" r="2.7"/>
                                    <path class="eye-slash" d="m4 4 16 16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-option" for="remember">
                            <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span class="custom-checkbox" aria-hidden="true"></span>
                            <span>Ingat saya</span>
                        </label>
                        <button class="help-link" type="button" data-open-help>Lupa password?</button>
                    </div>

                    <button class="submit-button" type="submit">
                        <span>Masuk</span>
                    </button>
                </form>

                <p class="account-note">Akun dikelola oleh pihak akademik UniNalar.</p>
            </div>
        </section>
    </main>

    <dialog class="help-dialog" id="accountHelpDialog" aria-labelledby="help-dialog-title">
        <div class="help-dialog-content">
            <button class="dialog-close" type="button" aria-label="Tutup" data-close-help>&times;</button>
            <span class="help-dialog-mark" aria-hidden="true">?</span>
            <h2 id="help-dialog-title">Perlu bantuan akses akun?</h2>
            <p>Tidak dapat mengakses akun? Silakan hubungi dosen pembimbing atau dosen yang bertanggung jawab untuk mendapatkan bantuan pemulihan akun.</p>
            <button class="dialog-confirm" type="button" data-close-help>Mengerti</button>
        </div>
    </dialog>
</body>
</html>
