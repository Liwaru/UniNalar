@extends('layouts.public')

@section('title', 'UniNalar — Kenali Risiko Lebih Dini')

@section('content')
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Navigasi utama">
        <div class="container landing-container">
            <a class="navbar-brand brand-lockup" href="#beranda" aria-label="UniNalar, Beranda">
                <img src="{{ asset('images/UniNalar Sidebar.png') }}" alt="UniNalar" width="174" height="58">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav" id="sectionNavigation">
                    <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cara-kerja">Cara Kerja</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang</a></li>
                </ul>
                <a class="btn btn-primary landing-btn landing-btn-nav ms-lg-4" href="{{ route('login') }}">Masuk</a>
            </div>
        </div>
    </nav>
</header>

<main id="konten" tabindex="-1">
    <section class="hero-section" id="beranda" aria-labelledby="hero-heading">
        <div class="container landing-container hero-layout">
            <div class="hero-copy">
                <div class="eyebrow">Sistem Informasi Akademik</div>
                <h1 id="hero-heading">Kenali Risiko Lebih Dini. <span class="text-uninlar-blue">Bantu Mahasiswa Melangkah Lebih Jauh.</span></h1>
                <p class="hero-description">Kelola KRS, nilai, dan informasi akademik. Tinjau hasil analisis serta rekomendasi yang telah tercatat.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary landing-btn landing-btn-primary" href="{{ route('login') }}">Masuk ke UniNalar</a>
                    <a class="btn landing-btn landing-btn-secondary" href="#fitur">Jelajahi fitur</a>
                </div>
            </div>
            <div class="hero-illustration">
                <svg viewBox="0 0 420 340" role="img" aria-labelledby="academic-illustration-title" xmlns="http://www.w3.org/2000/svg">
                    <title id="academic-illustration-title">Ilustrasi konseptual buku studi terbuka dan topi akademik</title>
                    <g stroke-linecap="round" stroke-linejoin="round">
                        <!-- A single open study book, grounded on its cover. No academic data is depicted. -->
                        <path class="study-cover" d="M37 155 205 182 377 155v128c-61-12-117-5-172 22-55-27-110-34-168-22Z"/>
                        <path class="study-page" d="M47 140c54-13 107-4 158 25v126c-51-29-104-38-158-25Z"/>
                        <path class="study-page study-page-tint" d="M205 165c51-29 104-38 158-25v126c-54-13-107-4-158 25Z"/>
                        <path class="study-binding" d="M205 171v111"/>
                        <path class="study-rule" d="M72 166c32-4 64 2 101 19M72 188c32-4 64 2 101 19M72 210c32-4 64 2 101 19M72 232c25-3 49 0 74 9"/>
                        <path class="study-rule" d="M234 194c34-15 65-20 99-16M234 216c34-15 65-20 99-16M234 238c23-10 45-15 67-16"/>
                        <path class="study-bookmark" d="M306 136c9-1 18-1 27 0v62l-13-8-14 10Z"/>
                        <!-- The cap and book share one composition rather than separate floating widgets. -->
                        <path class="study-cap-band" d="M235 94v26c17 17 55 17 74 0V94Z"/>
                        <path class="study-cap" d="m202 82 70-31 71 31-71 32Z"/>
                        <path class="study-tassel" d="m272 82 71 14v32"/>
                        <path class="study-tassel" d="m338 142 5-14 5 14"/>
                        <path class="study-baseline" d="M39 316h337"/>
                    </g>
                </svg>
            </div>
        </div>
    </section>

    <section class="features-section section-space" id="fitur" aria-labelledby="features-heading">
        <div class="container landing-container">
            <div class="section-heading text-center">
                <span class="section-kicker">PENDAMPINGAN YANG LEBIH TERARAH</span>
                <h2 id="features-heading">Data akademik menjadi <span class="text-uninlar-blue">langkah nyata</span></h2>
                <p>Satukan pemahaman, penjelasan, dan tindak lanjut dalam satu alur yang mudah digunakan.</p>
            </div>
            <div class="row g-4 feature-grid">
                <div class="col-md-6 col-xl-3"><article class="feature-card h-100"><div class="feature-icon icon-blue"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h17M7 15l4-4 3 2 6-7"/></svg></div><h3>Analisis risiko akademik</h3><p>Tinjau skor, kategori, dan riwayat hasil analisis yang telah tersimpan.</p><a href="#dashboard" aria-label="Lihat alur akademik">Lihat alur akademik <span aria-hidden="true">→</span></a></article></div>
                <div class="col-md-6 col-xl-3"><article class="feature-card h-100"><div class="feature-icon icon-teal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M5 7h10a4 4 0 0 1 0 8H8a3 3 0 0 0 0 6h11"/></svg></div><h3>Faktor yang transparan</h3><p>Pahami indikator yang berkontribusi pada hasil agar setiap penilaian dapat ditinjau kembali.</p><a href="#transparansi">Pelajari transparansi <span aria-hidden="true">→</span></a></article></div>
                <div class="col-md-6 col-xl-3"><article class="feature-card h-100"><div class="feature-icon icon-violet"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 14.3 9.7 21 12l-6.7 2.3L12 21l-2.3-6.7L3 12l6.7-2.3L12 3Z"/></svg></div><h3>Rekomendasi intervensi</h3><p>Ubah temuan menjadi opsi pendampingan yang relevan dan dapat dipilih oleh tim akademik.</p><a href="#cara-kerja">Lihat alur kerja <span aria-hidden="true">→</span></a></article></div>
                <div class="col-md-6 col-xl-3"><article class="feature-card h-100"><div class="feature-icon icon-amber"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5M12 3a9 9 0 1 0 9 9"/></svg></div><h3>Pemantauan tindak lanjut</h3><p>Pantau status tindak lanjut yang sudah tercatat untuk setiap mahasiswa.</p><a href="#cta">Mulai mengenal UniNalar <span aria-hidden="true">→</span></a></article></div>
            </div>
        </div>
    </section>

    <section class="dashboard-section section-space" id="dashboard">
        <div class="container landing-container">
            <div class="row align-items-end dashboard-intro g-4">
                <div class="col-lg-8"><span class="section-kicker">GAMBARAN YANG MUDAH DIBACA</span><h2>Ringkasan akademik, <span class="text-uninlar-blue">tanpa kehilangan konteks.</span></h2></div>
                <div class="col-lg-4"><p>Pengajuan, keputusan, dan penilaian memiliki status serta riwayat yang dapat ditinjau.</p></div>
            </div>
            <div class="academic-overview academic-flow" aria-label="Alur pencatatan akademik">
                <h3>Dari pengajuan hingga hasil studi</h3>
                <ol>
                    <li><strong>Susun dan ajukan KRS</strong><p>Mahasiswa memilih kelas pada periode yang dibuka.</p></li>
                    <li><strong>Tinjau pengajuan</strong><p>Admin Akademik mencatat persetujuan atau penolakan beserta riwayatnya.</p></li>
                    <li><strong>Catat dan finalisasi nilai</strong><p>Dosen mengelola penilaian kelas. Nilai draft tetap dibedakan dari nilai final.</p></li>
                    <li><strong>Tinjau hasil akademik</strong><p>IPS dan IPK menggunakan nilai final dari KRS yang disetujui.</p></li>
                </ol>
                <p class="overview-note">Halaman publik menjelaskan layanan. Data akademik tersedia setelah masuk sesuai peran Anda.</p>
            </div>
        </div>
    </section>

    <section class="workflow-section section-space" id="cara-kerja" aria-labelledby="workflow-heading">
        <div class="container landing-container">
            <div class="section-heading text-center"><span class="section-kicker">DARI DATA MENUJU DUKUNGAN</span><h2 id="workflow-heading">Empat tahap, <span class="text-uninlar-blue">satu tujuan.</span></h2><p>Teknologi membantu menyusun prioritas agar pendampingan akademik lebih terarah.</p></div>
            <div class="workflow-track row g-4">
                <div class="col-md-6 col-lg-3"><article class="workflow-step"><span class="step-number">01</span><div class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></div><h3>Data akademik</h3><p>Informasi akademik yang relevan menjadi titik awal analisis.</p></article></div>
                <div class="col-md-6 col-lg-3"><article class="workflow-step"><span class="step-number">02</span><div class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6"/></svg></div><h3>Tinjau hasil analisis</h3><p>Baca hasil tersimpan beserta sumbernya. Analisis otomatis belum diaktifkan.</p></article></div>
                <div class="col-md-6 col-lg-3"><article class="workflow-step"><span class="step-number">03</span><div class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M5 7h10a4 4 0 0 1 0 8H8a3 3 0 0 0 0 6h11"/></svg></div><h3>Faktor dan rekomendasi</h3><p>Tinjau faktor dan rekomendasi yang tercatat pada hasil analisis.</p></article></div>
                <div class="col-md-6 col-lg-3"><article class="workflow-step"><span class="step-number">04</span><div class="workflow-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7.5M12 3a9 9 0 1 0 9 9"/></svg></div><h3>Pantau tindak lanjut</h3><p>Tim akademik menentukan dukungan dan memantau progresnya.</p></article></div>
            </div>
        </div>
    </section>

    <section class="transparency-section section-space" id="transparansi">
        <div class="container landing-container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 transparency-copy"><span class="section-kicker">EXPLAINABLE AI · XAI</span><h2>Setiap sinyal punya <span class="text-uninlar-blue">alasan yang bisa ditelusuri.</span></h2><p>Rancangan analisis mengutamakan aturan yang transparan. Ringkasan deskriptif membantu membaca data yang tersedia. Skor dan kategori risiko resmi memerlukan metode yang ditetapkan institusi.</p><div class="transparency-points"><div><span class="point-check">✓</span><p><strong>Faktor terlihat jelas</strong><br>Indikator yang berkontribusi dapat diperiksa oleh pendamping.</p></div><div><span class="point-check">✓</span><p><strong>Rekomendasi tetap ditinjau manusia</strong><br>Keputusan intervensi berada pada tim akademik.</p></div></div><a class="text-link" href="{{ route('login') }}">Kenali UniNalar lebih dekat <span aria-hidden="true">→</span></a></div>
                <div class="col-lg-6">
                    <section class="academic-overview" aria-labelledby="analysis-sources-title">
                        <span class="mini-label">KONTEKS ANALISIS</span>
                        <h3 id="analysis-sources-title">Baca hasil beserta sumbernya</h3>
                        <dl class="service-list">
                            <div><dt>Data akademik</dt><dd>Nilai final, KRS yang disetujui, dan catatan kehadiran menjadi konteks ringkasan.</dd></div>
                            <div><dt>Status hasil</dt><dd>Hasil simulasi diberi label. Ringkasan deskriptif tidak menetapkan skor atau kategori resmi.</dd></div>
                            <div><dt>Keputusan pendampingan</dt><dd>Rekomendasi perlu ditinjau oleh pihak akademik sesuai kondisi mahasiswa.</dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section section-space" id="tentang" aria-labelledby="about-heading">
        <div class="container landing-container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 about-copy">
                    <span class="section-kicker">TENTANG UNINALAR</span>
                    <h2 id="about-heading">Memahami Risiko Akademik, <span class="text-uninlar-blue">Menentukan Langkah yang Lebih Tepat.</span></h2>
                    <p class="about-description">UniNalar adalah sistem informasi akademik yang membantu tim akademik memahami risiko mahasiswa melalui data yang terukur, analisis yang transparan, dan rekomendasi intervensi yang dapat ditinjau.</p>
                </div>
                <div class="col-lg-6">
                    <div class="about-principles">
                        <span class="mini-label">PRINSIP UNINALAR</span>
                        <article class="about-principle">
                            <span class="about-principle-number">01</span>
                            <div><h3>Berbasis Data</h3><p>Menggunakan informasi akademik seperti kehadiran dan tren nilai sebagai dasar analisis.</p></div>
                        </article>
                        <article class="about-principle">
                            <span class="about-principle-number">02</span>
                            <div><h3>Transparan dan Dapat Dijelaskan</h3><p>Membantu pengguna memahami faktor yang berkontribusi terhadap risiko mahasiswa.</p></div>
                        </article>
                        <article class="about-principle">
                            <span class="about-principle-number">03</span>
                            <div><h3>Mendukung Pendampingan</h3><p>Menyediakan rekomendasi intervensi sebagai bahan pertimbangan tim akademik, bukan pengganti keputusan manusia.</p></div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="closing-section" id="cta">
        <div class="container landing-container"><div class="closing-card"><div class="closing-glow" aria-hidden="true"></div><div class="closing-content"><span class="closing-kicker">MULAI DENGAN PEMAHAMAN</span><h2>Dukungan yang tepat dimulai dari <span class="text-uninlar-blue">melihat lebih awal.</span></h2><p>Kenali cara UniNalar membantu tim akademik memahami sinyal dan menindaklanjutinya.</p><a class="btn btn-light landing-btn closing-button" href="{{ route('login') }}">Masuk ke UniNalar</a></div><div class="closing-mark" aria-hidden="true"><span>U</span><i></i><b></b></div></div></div>
    </section>
</main>

<footer class="site-footer">
    <div class="container landing-container">
        <div class="footer-main row g-4">
            <div class="col-lg-5"><a class="footer-brand" href="#beranda"><img src="{{ asset('images/UniNalar Sidebar.png') }}" alt="UniNalar" width="160" height="54"></a><p>Sistem Informasi Akademik dengan analisis risiko yang transparan dan dukungan yang lebih terarah.</p></div>
            <div class="col-6 col-lg-2 footer-links"><strong>Navigasi</strong><a href="#fitur">Fitur</a><a href="#cara-kerja">Cara Kerja</a><a href="#tentang">Tentang</a></div>
            <div class="col-6 col-lg-2 footer-links"><strong>Akses</strong><a href="{{ route('login') }}">Masuk</a><a href="#dashboard">Alur akademik</a></div>
            <div class="col-lg-3 footer-privacy"><span class="privacy-icon" aria-hidden="true">⌑</span><strong>Privasi dan kepercayaan</strong><p>Data mahasiswa pribadi hanya tersedia melalui akun dengan kewenangan yang sesuai. Halaman publik tidak menampilkan data mahasiswa.</p></div>
        </div>
        <div class="footer-bottom"><span>© {{ date('Y') }} UniNalar. Dibuat untuk pendampingan akademik yang lebih baik.</span></div>
    </div>
</footer>
@endsection
