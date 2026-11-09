# UniNalar

Sistem informasi akademik berbasis Laravel 12, Blade, Bootstrap 5, dan Vite.
Empat role: Mahasiswa, Dosen, Admin Akademik, dan Pimpinan Yayasan.
Versi aktual dikunci oleh `composer.lock` dan `package-lock.json`.

## Dokumentasi

- [Audit sistem terbaru dengan Playwright MCP dan Chrome DevTools MCP](docs/final-full-system-dual-mcp-qa.md)
- [Review Git dan keamanan sebelum commit](docs/final-git-precommit-review.md)
- [Autentikasi username dan fixture development](docs/development-authentication-fixtures.md)
- [Runtime WSL terisolasi dan integrasi Groq](docs/groq-ai-integration.md)
- [Standar UI/UX seluruh halaman](docs/ui-ux-guidelines.md)
- [Audit konsistensi desain dan status MCP historis](docs/final-design-system-mcp-audit.md)
- [Penyempurnaan UI/UX dan verifikasi fungsional](docs/ui-ux-final-refinement.md)
- [Audit dan perbaikan UI/UX terarah](docs/ui-ux-targeted-audit.md)
- [Workflow dan aturan akademik](docs/academic-operations.md)
- [Audit pengembangan](docs/development-completion-audit.md)
- [Implementasi dan QA modul mahasiswa](docs/student-module-completion.md)
- [Implementasi dan QA modul dosen](docs/lecturer-module-completion.md)
- [Implementasi dan QA modul Admin Akademik](docs/admin-module-completion.md)
- [Implementasi dan QA modul Pimpinan Yayasan](docs/owner-module-completion.md)
- [Verifikasi sistem dan kesiapan rilis terbaru](docs/final-system-verification.md)
- [Persiapan shared hosting](docs/shared-hosting-readiness.md)
- [Skema dan relasi](database/README.md)
- [Docker development](docker/local/README.md)
- Riwayat [Phase 4](docs/phase4-performance-and-recovery.md),
  [Phase 5](docs/phase5-wsl-staging-and-cutover.md), dan
  [Phase 6](docs/phase6-native-build-and-fpm-readiness.md).

## Development dan pengujian

Gunakan filesystem Linux untuk workspace WSL. Siapkan dependency sesuai lockfile;
periksa scripts sebelum instalasi. `composer setup` menjalankan migration dan
**tidak boleh digunakan untuk menyiapkan QA atau database operasional**.
Jangan menyalin environment atau database operasional untuk membuat QA dapat boot.

```sh
# Membutuhkan runtime PHP dan dependency lokal yang sudah disiapkan.
php vendor/bin/phpunit --do-not-cache-result
php vendor/bin/pint --test
npm run build
```

Suite PHPUnit default mensyaratkan `APP_ENV=testing`, SQLite `:memory:`, dan
memverifikasinya sebelum operasi schema. `phpunit.xml` menyediakan kunci enkripsi
publik khusus test; nilai tersebut tidak boleh dipakai untuk deployment.
Frontend build harus tersedia untuk tes yang merender Blade dengan `@vite`.
Pint mengecualikan arsip `qa-artifacts`, bukan source aplikasi.

MySQL integration/concurrency membutuhkan instance QA terpisah dan manifest yang
lulus `Tests\Support\IsolatedMysql`; menjalankan suite terhadap database biasa
akan ditolak. Jangan mengarahkan test, fixture builder, migration, atau seeder ke
schema operasional `uninalar`.

## Batas fungsi saat ini

KRS mencatat versi pengajuan dan keputusan; penilaian dosen memiliki revisi dan
finalisasi. Ringkasan mahasiswa hanya memakai nilai final dari KRS yang disetujui.
Nilai draft pada halaman nilai diberi status eksplisit. IPS/IPK mengikuti skala
dan aturan yang tercatat di dokumentasi akademik.

Analisis risiko, faktor, rekomendasi, kehadiran, dan tindak lanjut menampilkan data
tersimpan. Algoritma AI/scoring dan workflow pencatatan kehadiran/tindak lanjut
belum tersedia. Integrasi Groq deskriptif tersedia secara terpisah untuk fixture
sintetis, atas permintaan eksplisit, dengan live API dinonaktifkan secara default.
Lihat panduan integrasi; belum ada klasifikasi risiko resmi. Jangan menganggap
ilustrasi landing page sebagai prediksi aktual.
Kebijakan resmi diperlukan sebelum mengaktifkan scoring, pembukaan nilai final,
atau koreksi SKS historis.

Migration operasional, backup/restore data nyata, cutover layanan, dan deployment
publik memerlukan proses serta persetujuan terpisah.
