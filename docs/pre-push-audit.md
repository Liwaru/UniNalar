# Final pre-push audit UniNalar

> Path `/path/to/UniNalar-main`, `~`, `<windows-user-home>` dan `<runtime-tag>`
> adalah placeholder publik pengganti identitas mesin pada bukti lokal. Ganti sesuai
> workspace/runtime development yang sudah diverifikasi sebelum memakai contoh perintah.

> Untuk working tree saat ini, gunakan [review precommit terbaru](final-git-precommit-review.md).
> Tidak ada staging, commit atau push pada review terbaru tersebut.

> Snapshot sebelum integrasi Groq/WSL. Setelah laporan ini dibuat, pekerjaan baru
> menambah source, migration, tes dan dokumentasi yang belum di-stage. Verdict dan
> allowlist 114 file di bawah berlaku untuk snapshot audit sebelumnya, bukan seluruh
> working tree terbaru. Audit/staging ulang diperlukan sebelum commit integrasi.

Tanggal: 8 November 2026. Verdict akhir: **SAFE TO COMMIT** untuk perubahan Git yang diaudit dan di-stage. Status awal NEEDS REVIEW telah diselesaikan melalui cleanup index dan aturan ignore spesifik. Ini bukan persetujuan deployment atau klaim kesiapan produksi.

## Git dan hasil akhir staging

- Workspace `/path/to/UniNalar-main`; branch `main`; HEAD `02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`.
- Remote fetch/push `https://github.com/Liwaru/UniNalar.git`; tracking `origin/main`, ahead/behind lokal 0/0. Tidak fetch; keadaan remote terbaru tidak diverifikasi.
- Baseline audit pertama: 48 modified + 64 file baru. Laporan audit menambah satu file menjadi **113** kandidat. Perubahan `.gitignore` pada tindak lanjut ini membuat allowlist **114 file**: **49 modified + 65 added**.
- Cleanup menambahkan **168 deleted dari index saja**; total staged **282 path** (49 M, 65 A, 168 D). D berarti keluar dari snapshot Git berikutnya; bukan penghapusan file fisik.
- Seluruh 113 kandidat sebelumnya tetap tercakup. Tidak ada source aplikasi diubah pada tindak lanjut ini; hanya `.gitignore` dan laporan audit diperbarui, ditambah staging yang diminta pengguna.
- Tidak commit, push, force push, reset, rewrite history, penghapusan file fisik atau deployment. Tidak ada perubahan DB operasional, Docker volume, .env, Compose atau konfigurasi operasional.

## Klasifikasi 168 artefak lama

Semua 168 path diperiksa menurut jenis, lokasi dan penggunaan; seluruh file teks dibaca dengan encoding UTF-8/UTF-16 yang sesuai. Rinciannya **92 YML, 34 PNG, 25 JSON, 12 TXT, 2 PATCH, 2 TAR, 1 PHP**. Tidak ada path yang diperlukan runtime aplikasi atau suite tes reusable.

| Kelompok | Jumlah | Keputusan dan alasan |
| --- | ---: | --- |
| `.playwright-mcp/` | 119 | Snapshot browser/screenshot sesi lama; bukan source atau fixture tes. Untrack, simpan lokal. |
| `qa-artifacts/phase2-1a1a6d919d4bade3/` | 21 | Log, manifest/inventory/fingerprint, patch, screenshot dan arsip baseline sesi QA. Untrack, simpan lokal sebagai bukti historis. |
| `qa-artifacts/phase3-p3-mw28gmsp/` | 28 | Log/hasil/screenshot/manifest, backup baseline dan skrip sekali pakai. Untrack, simpan lokal sebagai bukti historis. |

Satu PHP `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/qa-integrity-readonly.php` adalah probe sesi tertentu: bootstrap absolut `/qa/tests/Support/mysql-bootstrap.php`, verifikasi instance QA, ID fixture tetap dan tabel provenance QA. Bukan PHPUnit test, bukan autoload source, bukan dependency runtime. Tes reusable dan pengaman DB dalam `tests/Support/`, suites Feature/MySQL, factory, seeder dan migration tetap dilacak.

Pencarian dependency pada source aplikasi/tes/config/routes/resources/bootstrap tidak menemukan referensi path ke 168 file ini. Composer/PHPUnit/npm tidak menjadikannya entry point. `pint.json` justru mengecualikan arsip QA. Referensi dari dokumentasi historis adalah lokasi bukti lokal, bukan dependency aplikasi. Laporan historis dalam `docs/` tetap dilacak; bukti mentah tetap ada di disk tetapi tidak tersedia pada clone source baru. `docs/final-system-verification.md` tetap masuk staging dan sudah menjelaskan bukti lokal ignored.

Cleanup menggunakan `git --literal-pathspecs rm --cached --` dengan **168 nama file eksplisit** dari inventaris, tanpa glob atau penghapusan direktori fisik. SHA-256 masing-masing 168 file dibandingkan sebelum/sesudah: semuanya tetap ada dan byte-identik. SHA-256 112 kandidat aplikasi/tes/dokumentasi di luar laporan audit juga tetap sama pada tindak lanjut ini.

## Dua arsip besar dan riwayat

- `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/source-with-vendor.tar`: **77.726.208 byte**.
- `qa-artifacts/phase3-p3-mw28gmsp/baseline/source.tar`: **77.792.256 byte**.

Keduanya backup baseline dengan vendor, bukan source rilis. Audit sebelumnya memeriksa nama anggota arsip dan signature private key/provider token/APP_KEY tanpa temuan kredensial nyata. Tidak ditemukan .env operasional, auth.json atau DB lokal berdasarkan nama anggota; SQL milik vendor Sail adalah template testing PostgreSQL. Ini bukan sertifikasi seluruh arsip bebas data sensitif.

**Untracking hanya menghilangkan file dari snapshot commit berikutnya. Kedua arsip dan artefak lama tetap ada dalam history Git dan di disk.** Tidak dilakukan rewrite/purge history, garbage collection, penghapusan arsip atau force push. Ukuran clone/history lama tidak otomatis berkurang. Tidak ada file baru besar dalam staging.

## Perlindungan .gitignore

Aturan existing untuk qa-artifacts, .playwright-mcp, dependency/build, .env beserta variannya, auth.json dan runtime tetap dipertahankan. Aturan baru di-root ke lokasi lokal yang spesifik:

- `/backups/`, `/dumps/`, nama root backup/dump/database/uninalar SQL termasuk kompresinya; arsip backup/source, file root .bak dan editor backup.
- Database SQLite lokal di root termasuk journal/WAL; `database/.gitignore` existing tetap melindungi DB dalam database/.
- `.auth/`, `playwright/.auth/`, playwright-report, test-results, browser-profiles, browser-cookies*.json dan storage-state*.json di lokasi yang ditentukan.
- `.secrets/`, secrets.local/, credentials.local.json dan private key PEM/key lokal di root.

Tidak ditambahkan global `*.sql`, `*.json`, `*.zip` atau pengecualian source/fixture. **27 kontrol positif** ignore lulus; **8 kontrol source** tetap trackable, termasuk migration SQL, fixture SQL/PEM, factory, dokumentasi dan kedua `.env.*example` yang sah. Semua 168 artefak lama kini ignored dan tidak lagi tracked. File baru dengan nama di luar konvensi lokal ini tetap perlu review; jangan gunakan git add -f untuk secrets/artefak.

## Keamanan kandidat

Tidak ditemukan kredensial nyata, .env operasional, database lokal, dump SQL, sesi/cookie browser atau backup dalam 114 file allowlist. Signature private key/token provider umum diperiksa kembali; audit sebelumnya juga meninjau assignment password/token/secret. Key di phpunit.xml adalah kunci publik khusus QA bernilai nol, bukan secret operasional. Password test/demo dan penanda PRIVATE/SECRET adalah fixture sintetis. Kedua environment example memiliki nilai secret kosong/null dan tetap dilacak.

Batas pemeriksaan: tidak memindai seluruh revisi history, bukan entropy scanner menyeluruh atau validasi token ke provider. Jika nanti ditemukan secret pada history, rotasi dan penanganan history perlu proses terpisah.

## Kelengkapan dan keselarasan


- Mahasiswa: controller/dashboard/KRS, service, delapan view beserta partial, suite Feature/MySQL dan skenario reusable tercakup.
- Dosen: controller kelas/profil baru, dashboard/nilai, service, tujuh halaman beserta partial dan suite Feature/MySQL tercakup.
- Admin Akademik: dashboard/service baru, controller master/kelas/review KRS, detail/form/list/partial dan suite Feature/MySQL tercakup. Perubahan ini mencakup implementasi, bukan dokumentasi saja.
- Owner/Kaprodi: monitoring controller/service baru, dashboard, halaman monitoring/detail/partial, suite Feature/MySQL tercakup.
- Final QA/UI: shared layout/CSS/login/landing, pagination Indonesia beserta provider, navigasi, route, security/academic-summary tests, Pint/Vite/phpunit configuration serta laporan final tercakup.
- README menautkan laporan semua modul dan `docs/final-system-verification.md`; tautan Markdown lokal pada README/laporan baru valid di workspace.
- Laporan final membedakan hasil QA lokal dari kesiapan produksi dan batas spesifikasi. Bukti browser/log yang disebut ada lokal dan sengaja ignored; tautan screenshot tidak tersedia pada clone hanya source. Tidak mengklaim bukti mentah akan ikut push.
- Bukti lokal final yang dirujuk tersedia: sqlite-regression.log, mysql-tests.log, http-checks.json, browser-summary.json di qa-artifacts/final. Angka dalam laporan adalah hasil sesi QA terdahulu, bukan tes yang dijalankan ulang audit Git ini.
- Tidak ada perubahan model/migration, lockfile, .env atau Docker operational configuration dalam kandidat.
- README muncul sebagai binary pada Git karena blob HEAD memiliki 12 byte NUL; versi baru UTF-8 tanpa NUL, berisi dokumentasi proyek aktual. Penulisan ulang ini sesuai dokumentasi modul/final, bukan binary tambahan.
- Perubahan Vite mengatur pipeline charset/minifikasi, AppServiceProvider mendaftarkan pagination, config/dashboard menambahkan navigasi; semuanya terkait implementasi yang dilaporkan.

## Pemeriksaan akhir

- `git diff --cached --check`: PASS.
- `git diff --cached --stat` dan `git diff --cached --name-status`: diperiksa; tepat 49 M, 65 A, 168 D, tanpa rename/perubahan mode.
- `git diff --check`: PASS; tidak ada perubahan tertinggal di luar staging atau file baru non-ignored.
- Set D sama persis dengan 168 path baseline; seluruh path lain tetap dilacak. Tidak ada penghapusan source/tests/docs/migration/fixture.
- Tidak ada tracked file yang masih cocok ignore; semua file allowlist masuk staging dan tidak di-ignore.
- Tidak ada symlink atau file besar baru; semua 168 artefak fisik byte-identik. Kandidat aplikasi/tes tidak diubah pada sesi cleanup.
- QA aplikasi/build/browser tidak dijalankan ulang: sesi ini hanya mengubah aturan ignore dan dokumentasi serta index Git.

Lockfile tidak berubah:
- composer.lock SHA-256 `a364316d5f1b1eb2e3c0c74cf585b1d5940fee41ddf9dc7db9dcd312131d8ecb`
- package-lock.json SHA-256 `8094a59b5b09337c9d869fd9a15572a2a936b3936b3792f99f3347e17382d721`

## Allowlist dan perintah manual berikutnya

Allowlist staging diperbarui pada `/tmp/uninalar-pre-push-allowlist.txt` (114 path A/M). Daftar cleanup ada di `/tmp/uninalar-pre-push-exclude-tracked.txt` (168 path D). Keduanya inventaris lokal sementara, tidak di-commit; salinannya lengkap di bagian berikut. Staging **sudah dilakukan**, termasuk cleanup. Jika ada perubahan baru, periksa ulang diff sebelum commit.

```sh
cd /path/to/UniNalar-main
git diff --cached --check
git diff --cached --stat
git diff --cached --name-status
git commit -m "Complete academic modules and remove tracked QA artifacts"
git push origin main
```

Commit dan push di atas untuk dijalankan pengguna secara manual; keduanya tidak dilakukan pada audit ini. Tidak perlu git add seluruh direktori lagi.

## AMAN DI-COMMIT — 114 file added/modified

- M: `.gitignore`
- M: `README.md`
- M: `app/Http/Controllers/AdminAcademicController.php`
- A: `app/Http/Controllers/AdminDashboardController.php`
- M: `app/Http/Controllers/AdminKelasKuliahController.php`
- M: `app/Http/Controllers/AdminKrsReviewController.php`
- M: `app/Http/Controllers/DosenDashboardController.php`
- A: `app/Http/Controllers/DosenKelasController.php`
- M: `app/Http/Controllers/DosenNilaiController.php`
- A: `app/Http/Controllers/DosenProfilController.php`
- M: `app/Http/Controllers/MahasiswaDashboardController.php`
- M: `app/Http/Controllers/MahasiswaKrsController.php`
- M: `app/Http/Controllers/OwnerDashboardController.php`
- A: `app/Http/Controllers/OwnerMonitoringController.php`
- M: `app/Providers/AppServiceProvider.php`
- M: `app/Services/AcademicGradeSummary.php`
- A: `app/Services/AdminAcademicData.php`
- M: `app/Services/LecturerAcademicData.php`
- M: `app/Services/MahasiswaOperationalData.php`
- A: `app/Services/OwnerAcademicData.php`
- M: `config/dashboard.php`
- M: `docs/academic-operations.md`
- A: `docs/admin-module-completion.md`
- A: `docs/development-completion-audit.md`
- A: `docs/final-system-verification.md`
- A: `docs/lecturer-module-completion.md`
- A: `docs/owner-module-completion.md`
- A: `docs/pre-push-audit.md`
- A: `docs/shared-hosting-readiness.md`
- A: `docs/student-module-completion.md`
- A: `docs/ui-ux-guidelines.md`
- M: `phpunit.xml`
- A: `pint.json`
- M: `resources/css/admin-academic.css`
- M: `resources/css/landing.css`
- M: `resources/css/login.css`
- M: `resources/css/student-dashboard.css`
- M: `resources/views/auth/login.blade.php`
- A: `resources/views/components/academic-pagination.blade.php`
- M: `resources/views/dashboard/admin-akademik/index.blade.php`
- M: `resources/views/dashboard/admin-akademik/kelas-detail.blade.php`
- A: `resources/views/dashboard/admin-akademik/krs-detail.blade.php`
- M: `resources/views/dashboard/admin-akademik/krs-review.blade.php`
- A: `resources/views/dashboard/admin-akademik/partials/decision.blade.php`
- A: `resources/views/dashboard/admin-akademik/partials/filters.blade.php`
- A: `resources/views/dashboard/admin-akademik/resource-detail.blade.php`
- M: `resources/views/dashboard/admin-akademik/resource-form.blade.php`
- M: `resources/views/dashboard/admin-akademik/resource-index.blade.php`
- M: `resources/views/dashboard/dosen/index.blade.php`
- A: `resources/views/dashboard/dosen/kehadiran.blade.php`
- A: `resources/views/dashboard/dosen/kelas-detail.blade.php`
- A: `resources/views/dashboard/dosen/kelas.blade.php`
- M: `resources/views/dashboard/dosen/nilai.blade.php`
- A: `resources/views/dashboard/dosen/partials/class-links.blade.php`
- A: `resources/views/dashboard/dosen/partials/filters.blade.php`
- A: `resources/views/dashboard/dosen/partials/state.blade.php`
- A: `resources/views/dashboard/dosen/peserta.blade.php`
- A: `resources/views/dashboard/dosen/profil.blade.php`
- M: `resources/views/dashboard/mahasiswa/analisis-risiko.blade.php`
- M: `resources/views/dashboard/mahasiswa/index.blade.php`
- M: `resources/views/dashboard/mahasiswa/kehadiran.blade.php`
- M: `resources/views/dashboard/mahasiswa/krs.blade.php`
- M: `resources/views/dashboard/mahasiswa/nilai.blade.php`
- A: `resources/views/dashboard/mahasiswa/partials/grade-summary.blade.php`
- A: `resources/views/dashboard/mahasiswa/partials/semester-filter.blade.php`
- M: `resources/views/dashboard/mahasiswa/profil.blade.php`
- M: `resources/views/dashboard/mahasiswa/rekomendasi.blade.php`
- M: `resources/views/dashboard/mahasiswa/tindak-lanjut.blade.php`
- A: `resources/views/dashboard/owner/attendance.blade.php`
- A: `resources/views/dashboard/owner/class.blade.php`
- A: `resources/views/dashboard/owner/classes.blade.php`
- A: `resources/views/dashboard/owner/follow-ups.blade.php`
- A: `resources/views/dashboard/owner/grades.blade.php`
- M: `resources/views/dashboard/owner/index.blade.php`
- A: `resources/views/dashboard/owner/krs-detail.blade.php`
- A: `resources/views/dashboard/owner/krs.blade.php`
- A: `resources/views/dashboard/owner/layout.blade.php`
- A: `resources/views/dashboard/owner/lecturer.blade.php`
- A: `resources/views/dashboard/owner/lecturers.blade.php`
- A: `resources/views/dashboard/owner/partials/class-table.blade.php`
- A: `resources/views/dashboard/owner/partials/empty.blade.php`
- A: `resources/views/dashboard/owner/partials/filters.blade.php`
- A: `resources/views/dashboard/owner/partials/pagination.blade.php`
- A: `resources/views/dashboard/owner/recommendations.blade.php`
- A: `resources/views/dashboard/owner/reports.blade.php`
- A: `resources/views/dashboard/owner/risk.blade.php`
- A: `resources/views/dashboard/owner/risks.blade.php`
- A: `resources/views/dashboard/owner/student.blade.php`
- A: `resources/views/dashboard/owner/students.blade.php`
- M: `resources/views/landing/index.blade.php`
- M: `resources/views/layouts/dashboard.blade.php`
- M: `routes/web.php`
- M: `tests/Feature/AdminFormStateTest.php`
- A: `tests/Feature/AdminModuleTest.php`
- M: `tests/Feature/DemoAccountAuthenticationTest.php`
- A: `tests/Feature/FinalSystemSecurityTest.php`
- M: `tests/Feature/LandingPageTest.php`
- A: `tests/Feature/LecturerModuleTest.php`
- M: `tests/Feature/MahasiswaDashboardTest.php`
- A: `tests/Feature/OwnerModuleTest.php`
- A: `tests/Feature/PublishedAcademicSummaryTest.php`
- A: `tests/Feature/StudentModuleTest.php`
- M: `tests/Mysql/AdminMasterTest.php`
- A: `tests/Mysql/AdminModuleTest.php`
- M: `tests/Mysql/ConcurrencyTest.php`
- A: `tests/Mysql/LecturerModuleTest.php`
- A: `tests/Mysql/OwnerModuleTest.php`
- A: `tests/Mysql/StudentModuleTest.php`
- A: `tests/Support/AdminModuleScenarios.php`
- A: `tests/Support/LecturerModuleScenarios.php`
- A: `tests/Support/OwnerModuleScenarios.php`
- A: `tests/Support/StudentModuleScenarios.php`
- M: `tests/Support/mysql-race-worker.php`
- M: `vite.config.js`

## Dikeluarkan dari tracking — 168 path, file fisik tetap ada

- D: `.playwright-mcp/page-2026-10-29T10-49-47-176Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-49-59-021Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-52-44-171Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-52-58-925Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-53-24-718Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-54-00-037Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-54-15-569Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-54-28-600Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-54-44-575Z.png`
- D: `.playwright-mcp/page-2026-10-29T10-54-56-440Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-55-24-587Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-55-34-102Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-55-37-718Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-55-46-332Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-55-48-901Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-56-52-666Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-57-12-610Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-57-25-310Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-57-38-351Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-57-50-467Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-57-58-715Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-58-06-093Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-58-17-500Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-58-41-170Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-58-50-894Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-58-57-713Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-59-04-446Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-59-11-927Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-59-40-227Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-59-51-346Z.yml`
- D: `.playwright-mcp/page-2026-10-29T10-59-59-019Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-00-09-843Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-00-18-603Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-00-35-926Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-02-23-653Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-02-38-197Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-02-50-827Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-02-58-821Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-03-06-547Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-03-21-087Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-03-24-875Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-03-34-982Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-03-46-414Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-04-39-753Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-04-59-739Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-05-23-510Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-05-49-007Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-05-51-806Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-06-36-137Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-14-26-430Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-19-09-158Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-19-56-693Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-20-26-285Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-20-52-278Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-21-36-571Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-21-45-857Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-21-56-271Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-22-04-163Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-22-13-685Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-22-22-670Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-22-28-036Z.png`
- D: `.playwright-mcp/page-2026-10-29T11-23-05-164Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-23-23-300Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-23-53-394Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-24-09-796Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-24-11-835Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-24-23-148Z.png`
- D: `.playwright-mcp/page-2026-10-29T11-24-48-073Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-25-01-160Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-25-03-191Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-25-18-859Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-25-47-865Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-25-59-416Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-26-11-545Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-26-20-994Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-26-58-774Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-27-34-887Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-27-57-417Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-29-41-384Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-30-05-882Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-30-40-704Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-31-05-440Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-31-13-586Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-31-33-654Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-32-20-357Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-33-41-745Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-34-12-425Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-34-22-698Z.yml`
- D: `.playwright-mcp/page-2026-10-29T11-34-39-969Z.png`
- D: `.playwright-mcp/page-2026-10-29T11-35-37-140Z.png`
- D: `.playwright-mcp/page-2026-11-04T11-22-10-752Z.yml`
- D: `.playwright-mcp/page-2026-11-04T11-36-50-236Z.yml`
- D: `.playwright-mcp/page-2026-11-04T12-41-07-819Z.yml`
- D: `.playwright-mcp/page-2026-11-04T14-18-59-042Z.yml`
- D: `.playwright-mcp/page-2026-11-04T14-38-56-873Z.yml`
- D: `.playwright-mcp/page-2026-11-04T14-41-46-856Z.yml`
- D: `.playwright-mcp/page-2026-11-04T14-53-53-062Z.yml`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/admin-isolated.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/class-form-mobile-final.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/grades-final-isolated.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-after-1366.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-after-1920.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-after-375.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-after-768.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-final-1366.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-final-1920.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-final-375.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/landing-final-768.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/login-after-1366.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/login-after-1920.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/login-after-375.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/login-after-768.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/menu-mobile-final.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-desktop-final.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-isolated-1366.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-isolated-1920.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-isolated-375.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-isolated-768.png`
- D: `.playwright-mcp/qa-20261104-1a1a6a8cc3b/owner-mobile-final.png`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/artisan-benchmark.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/existing-source.patch`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/git-status.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/operational-fingerprint.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/source-inventory.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/baseline/source-with-vendor.tar`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/changed-existing-source.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/final-safety.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/landing-about-desktop.png`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/linux-isolation.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/mysql-final-tests.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/overhead-up-benchmark.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/owner-desktop.png`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/results.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/schema-review.json`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/sqlite-final-tests.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/student-krs-desktop.png`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/synthetic-restore-checksums.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/synthetic-workflow-restore-checksums.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/vite-build.txt`
- D: `qa-artifacts/phase2-1a1a6d919d4bade3/windows-isolation.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/academic-journey-data.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/artisan-benchmark.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/baseline/files.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/baseline/git-status.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/baseline/source.tar`
- D: `qa-artifacts/phase3-p3-mw28gmsp/baseline/user-changes.patch`
- D: `qa-artifacts/phase3-p3-mw28gmsp/final-browser-data.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/http-controlled.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/isolation.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/mysql-tests.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/populated-data.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/browser-data.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/changed-existing-files.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/cleanup.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/final-results.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/mysql-tests.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/qa-integrity-readonly.php`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/qa-integrity.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/sqlite-tests.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/student-desktop.png`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/student-krs-mobile.png`
- D: `qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/vite-build.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/schema-review.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/sqlite-tests.txt`
- D: `qa-artifacts/phase3-p3-mw28gmsp/student-final-desktop.png`
- D: `qa-artifacts/phase3-p3-mw28gmsp/student-krs-desktop.png`
- D: `qa-artifacts/phase3-p3-mw28gmsp/tests-isolation.json`
- D: `qa-artifacts/phase3-p3-mw28gmsp/windows-isolation.json`
