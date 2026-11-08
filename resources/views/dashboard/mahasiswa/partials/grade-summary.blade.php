<section class="student-section" aria-labelledby="grade-summary-title">
    <article class="student-card">
        <h2 id="grade-summary-title" class="academic-card-title">IPS dan IPK</h2>
        <p>IPS{{ $gradeSummary['ips_semester'] ? ' · '.$gradeSummary['ips_semester'] : '' }}: <strong>{{ $gradeSummary['ips'] !== null ? number_format($gradeSummary['ips'], 2) : 'Belum tersedia' }}</strong></p>
        <p>IPK: <strong>{{ $gradeSummary['ipk'] !== null ? number_format($gradeSummary['ipk'], 2) : 'Belum tersedia' }}</strong></p>
        <p class="card-subtitle">Seluruh riwayat nilai final valid, berbobot SKS; filter semester hanya membatasi tabel nilai. {{ $gradeSummary['final_count'] }} catatan nilai final valid.</p>
    </article>
</section>
