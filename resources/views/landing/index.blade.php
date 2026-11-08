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
                <ul class="navbar-nav ms-auto align-items-lg-center" id="sectionNavigation">
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

<main id="konten">
    <section class="hero-section" id="beranda" aria-labelledby="hero-heading">
        <div class="container landing-container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 hero-copy">
                    <div class="eyebrow"><span class="eyebrow-dot"></span> Sistem Informasi Akademik</div>
                    <h1 id="hero-heading">Kenali Risiko Lebih Dini. <span class="text-uninlar-blue">Bantu Mahasiswa Melangkah Lebih Jauh.</span></h1>
                    <p class="hero-description">Kelola KRS, nilai, dan informasi akademik. Tinjau hasil analisis serta rekomendasi yang telah tercatat.</p>
                    <div class="hero-actions">
                        <a class="btn btn-primary landing-btn landing-btn-primary" href="{{ route('login') }}">Masuk ke UniNalar</a>
                        <a class="btn landing-btn landing-btn-secondary" href="#fitur">Jelajahi fitur</a>
                    </div>
                    <div class="hero-proof"><span class="proof-mark" aria-hidden="true">✓</span> Analisis yang dapat dipahami, untuk pendampingan yang lebih tepat.</div>
                </div>
                <div class="col-lg-6 hero-visual-wrap">
                    <div class="hero-visual" aria-label="Ilustrasi dashboard akademik dan analisis AI simulasi">
                        <div class="orb orb-one"></div><div class="orb orb-two"></div>
                        <div class="hero-dashboard-card rounded-2xl">
                            <div class="mini-dashboard-top">
                                <div><span class="mini-label">RINGKASAN AKADEMIK</span><strong>Ikhtisar mahasiswa</strong></div>
                                <span class="live-dot" title="Visual simulasi"></span>
                            </div>
                            <div class="mini-stat-grid">
                                <div class="mini-stat"><span>Risiko terpantau</span><strong>18.4%</strong><em>simulasi</em></div>
                                <div class="mini-stat"><span>Intervensi aktif</span><strong>32</strong><em>simulasi</em></div>
                            </div>
                            <div class="mini-chart-heading"><span>Tren risiko akademik</span><span>Data ilustrasi</span></div>
                            <svg class="mini-chart" viewBox="0 0 420 110" role="img" aria-label="Grafik garis ilustratif tren risiko akademik">
                                <defs><linearGradient id="heroChartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#2563eb" stop-opacity=".2"/><stop offset="1" stop-color="#2563eb" stop-opacity="0"/></linearGradient></defs>
                                <path d="M0 25H420M0 65H420M0 105H420" stroke="#e8edf5" stroke-dasharray="4 5"/>
                                <path d="M0 43 C35 45 38 32 72 38 S118 58 146 50 S184 55 216 70 S261 56 286 64 S327 82 350 73 S388 88 420 82 V110 H0Z" fill="url(#heroChartFill)"/>
                                <path d="M0 43 C35 45 38 32 72 38 S118 58 146 50 S184 55 216 70 S261 56 286 64 S327 82 350 73 S388 88 420 82" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                            <div class="mini-chart-labels"><span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>Mei</span><span>Jun</span></div>
                        </div>
                        <div class="ai-insight-card">
                            <div class="ai-icon" aria-hidden="true"><span>✦</span></div>
                            <div><span class="ai-caption">INSIGHT AI · SIMULASI</span><p>Kehadiran dan tren nilai menjadi faktor yang perlu diperhatikan.</p></div>
                            <span class="insight-spark" aria-hidden="true">↗</span>
                        </div>
                        <div class="hero-orbit-label"><span class="orbit-pulse"></span> Data → Insight → Aksi</div>
                        <div class="hero-decoration hero-decoration-top" aria-hidden="true">✳</div>
                        <div class="hero-decoration hero-decoration-bottom" aria-hidden="true">+</div>
                    </div>
                </div>
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
                <div class="col-md-6 col-xl-3"><article class="feature-card h-100"><div class="feature-icon icon-blue"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h17M7 15l4-4 3 2 6-7"/></svg></div><h3>Analisis risiko akademik</h3><p>Tinjau skor, kategori, dan riwayat hasil analisis yang telah tersimpan.</p><a href="#dashboard" aria-label="Lihat preview analisis risiko">Lihat preview <span aria-hidden="true">→</span></a></article></div>
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
                <div class="col-lg-4"><p>Contoh tampilan untuk membantu tim melihat pola, bukan menggantikan penilaian pendamping.</p></div>
            </div>
            <div class="dashboard-shell">
                <div class="dashboard-topbar"><div class="dashboard-brand"><img src="{{ asset('images/UniNalar Icon.png') }}" alt="" width="32" height="32"><strong>UniNalar <span>/ Ikhtisar</span></strong></div><div class="dashboard-top-actions"><span class="simulation-tag"><i></i> Data simulasi</span><span class="avatar-demo" aria-label="Pengguna demo">U</span></div></div>
                <div class="dashboard-body">
                    <aside class="dashboard-sidebar" aria-label="Navigasi contoh dashboard"><span class="sidebar-active"><i></i> Ringkasan</span><span><i></i> Mahasiswa</span><span><i></i> Analisis risiko</span><span><i></i> Intervensi</span></aside>
                    <div class="dashboard-main">
                        <div class="dashboard-heading"><div><span class="mini-label">DASHBOARD AKADEMIK</span><h3>Selamat pagi, Tim Akademik</h3><p>Berikut ringkasan aktivitas pendampingan.</p></div><button class="btn btn-sm dashboard-filter" type="button" aria-label="Filter semester simulasi">Semester berjalan</button></div>
                        <div class="row g-3 metric-row">
                            <div class="col-6 col-xl-3"><div class="metric-card"><span>Total mahasiswa</span><strong>1.248</strong><small><b class="metric-up">+4,8%</b> dari periode lalu</small><i class="metric-mark mark-blue">◎</i></div></div>
                            <div class="col-6 col-xl-3"><div class="metric-card"><span>Perlu perhatian</span><strong>186</strong><small><b class="metric-warn">14,9%</b> dari total</small><i class="metric-mark mark-amber">!</i></div></div>
                            <div class="col-6 col-xl-3"><div class="metric-card"><span>Intervensi aktif</span><strong>74</strong><small>Dalam pemantauan</small><i class="metric-mark mark-teal">↗</i></div></div>
                            <div class="col-6 col-xl-3"><div class="metric-card"><span>Tindak lanjut selesai</span><strong>62%</strong><small><b class="metric-up">+8,2%</b> bulan ini</small><i class="metric-mark mark-violet">✓</i></div></div>
                        </div>
                        <div class="row g-3 chart-row">
                            <div class="col-xl-8"><div class="chart-card"><div class="chart-card-heading"><div><h4>Tren risiko akademik</h4><p>Perbandingan kategori risiko · ilustrasi</p></div><span class="chart-legend"><i class="legend-blue"></i> Perlu perhatian <i class="legend-teal"></i> Stabil</span></div><div class="chart-canvas-wrap"><canvas id="riskTrendChart" role="img" aria-label="Grafik simulasi tren risiko akademik"></canvas></div></div></div>
                            <div class="col-xl-4"><div class="chart-card distribution-card"><div class="chart-card-heading"><div><h4>Distribusi risiko</h4><p>Proporsi mahasiswa · ilustrasi</p></div><span class="chart-more" aria-hidden="true">···</span></div><div class="donut-wrap"><canvas id="riskDistributionChart" role="img" aria-label="Grafik simulasi distribusi kategori risiko"></canvas><div class="donut-center"><strong>1.248</strong><span>simulasi</span></div></div><div class="distribution-legend"><span><i class="legend-teal"></i> Stabil <b>64%</b></span><span><i class="legend-blue"></i> Pantau <b>25%</b></span><span><i class="legend-amber"></i> Prioritas <b>11%</b></span></div></div></div>
                        </div>
                        <p class="dashboard-disclaimer"><span aria-hidden="true">ⓘ</span> Seluruh angka, kategori, grafik, dan nama pada preview ini adalah data simulasi.</p>
                    </div>
                </div>
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
                <div class="col-lg-6 transparency-copy"><span class="section-kicker">EXPLAINABLE AI · XAI</span><h2>Setiap sinyal punya <span class="text-uninlar-blue">alasan yang bisa ditelusuri.</span></h2><p>Rancangan analisis mengutamakan aturan yang transparan. Panel di samping adalah ilustrasi; perhitungan risiko otomatis dan penjelasan AI belum tersedia dalam aplikasi.</p><div class="transparency-points"><div><span class="point-check">✓</span><p><strong>Faktor terlihat jelas</strong><br>Indikator yang berkontribusi dapat diperiksa oleh pendamping.</p></div><div><span class="point-check">✓</span><p><strong>Rekomendasi tetap ditinjau manusia</strong><br>Keputusan intervensi berada pada tim akademik.</p></div></div><a class="text-link" href="{{ route('login') }}">Kenali UniNalar lebih dekat <span aria-hidden="true">→</span></a></div>
                <div class="col-lg-6"><div class="explanation-card"><div class="explanation-header"><div><span class="mini-label">CONTOH PENJELASAN</span><h3>Faktor yang perlu diperhatikan</h3></div><span class="simulation-tag"><i></i> Ilustrasi</span></div><div class="risk-sample"><div class="risk-score"><span>Skor risiko</span><strong>68<span>/100</span></strong><small>Nilai contoh, bukan prediksi aktual</small></div><div class="risk-status"><span class="status-indicator"></span> Perlu perhatian</div></div><div class="factor-list"><div class="factor-row"><div class="factor-title"><span>Kehadiran kuliah</span><b>Kontribusi ilustratif 35%</b></div><div class="factor-track"><i style="width: 72%"></i></div></div><div class="factor-row"><div class="factor-title"><span>Tren nilai</span><b>Kontribusi ilustratif 30%</b></div><div class="factor-track"><i style="width: 61%"></i></div></div><div class="factor-row"><div class="factor-title"><span>Ketuntasan studi</span><b>Kontribusi ilustratif 20%</b></div><div class="factor-track"><i style="width: 44%"></i></div></div></div><div class="recommendation-note"><span class="recommendation-icon" aria-hidden="true">✦</span><p><strong>Opsi tindak lanjut</strong><br>Jadwalkan percakapan akademik untuk memahami hambatan kehadiran.</p></div><p class="illustration-note">Faktor dan bobot di atas hanya ilustrasi. Aturan sebenarnya ditetapkan institusi.</p></div></div>
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
            <div class="col-6 col-lg-2 footer-links"><strong>Akses</strong><a href="{{ route('login') }}">Masuk</a><a href="#dashboard">Preview dashboard</a></div>
            <div class="col-lg-3 footer-privacy"><span class="privacy-icon" aria-hidden="true">⌑</span><strong>Privasi dan kepercayaan</strong><p>Preview publik hanya menggunakan data simulasi. Data mahasiswa pribadi tidak ditampilkan di halaman ini.</p></div>
        </div>
        <div class="footer-bottom"><span>© {{ date('Y') }} UniNalar. Dibuat untuk pendampingan akademik yang lebih baik.</span></div>
    </div>
</footer>
@endsection
