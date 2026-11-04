<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <meta name="description" content="Pahami kondisi akademik mahasiswa melalui analisis risiko yang transparan dan rekomendasi intervensi yang terarah.">
    <title>@yield('title', 'UniNalar — Analisis Akademik yang Lebih Terarah')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body class="landing-page font-sans">
    <a class="skip-link" href="#konten">Lewati ke konten</a>
    @yield('content')
</body>
</html>
