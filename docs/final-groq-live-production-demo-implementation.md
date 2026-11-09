# Implementasi aman Groq live untuk production demo

Tanggal: 9 November 2026. Branch `main`; HEAD yang diperiksa tetap `fca168c`. Perubahan belum di-stage/commit. Dasar: [audit deployment](uninalar-demo-deployment-readiness.md), [analisis Groq hosting](groq-live-shared-hosting-readiness.md), dan [bukti integrasi historis](groq-ai-integration.md). Kedua laporan audit sebelumnya menggambarkan source sebelum implementasi ini.

## Keputusan

**CONDITIONAL GO untuk menyiapkan staging demo; NO-GO untuk mengaktifkan live publik sekarang.** Source mempunyai jalur production demo eksplisit, dengan default nonaktif, tetapi database MySQL/MariaDB dan shared hosting aktual belum diuji. Lulus mock bukan bukti kompatibilitas hosting atau izin request berbayar.

Tidak ada request Groq live, deployment, instalasi dependency, perubahan `.env`/APP_KEY, migration database utama, akses MySQL operasional, atau operasi Git mutatif. Container PHP existing hanya dipakai untuk proses QA; konfigurasi/container/network Docker tidak diubah. Build memakai dependency Node existing.

## Temuan awal dan perubahan

Source awal menonaktifkan tombol di luar local/testing dan memverifikasi manifest/path/SQLite WSL sebelum generate/reuse. Client HTTP sendiri dapat digunakan pada PHP biasa. Query reuse belum memfilter transport; kuota hanya cache per menit; RuntimeException mempunyai pesan environment yang terlalu umum.

Implementasi mempertahankan guard WSL dan memindahkan keputusan akses/readiness ke satu policy. Jalur production baru tidak menggunakan manifest WSL dan tidak membuat initializer data hosting. Initializer dataset/accounts hosting tetap merupakan pekerjaan terpisah sebelum staging.

| File source | Perubahan |
| --- | --- |
| `app/Services/GroqLivePolicy.php` — baru | Policy environment/mode, identitas DB demo, allowlist akun, keamanan konfigurasi, transport dan readiness bersama. |
| `app/Services/GroqDemoBudget.php` — baru | Reservasi attempt persisten, batas global/user, window per menit, lease global dan pemeriksaan token sebelum simpan. |
| `database/migrations/2026_11_10_000000_create_groq_demo_budgets.php` — baru | Dua tabel budget kecil; satu baris global dibuat dalam migration. Tidak dijalankan pada DB development. |
| `app/Services/GroqAcademicAnalysis.php` | Policy akses, reuse berdasarkan transport, budget production, recheck reuse setelah reservasi, transaksi hasil dan pesan kegagalan simpan. |
| `app/Services/GroqAcademicClient.php` | Menggunakan validasi provider/provenance dari policy; HTTP client, TLS, timeout, schema dan tanpa retry tetap dipertahankan. |
| `app/Http/Controllers/MahasiswaAiController.php` | Pesan RuntimeException aman untuk gangguan runtime/cache/DB; own-profile, consent, validasi dan redirect tetap. |
| `config/services.php` | Konfigurasi demo default OFF; parsing boolean yang tidak dikenal menjadi null dan ditolak. |
| `.env.example` | Nama konfigurasi baru dengan enabled false, allowlist kosong dan batas 0. Tidak berisi key nyata. |
| `resources/views/dashboard/mahasiswa/analisis-risiko.blade.php` | Memakai readiness policy yang sama dengan backend, pesan aman dan state existing. Tidak merombak UI empat role. |
| `tests/Feature/GroqProductionDemoTest.php` — baru | 13 tes policy/budget/provenance/kegagalan/race. |
| `tests/Support/groq-budget-worker.php` — baru | Worker QA khusus `/tmp` tanpa dotenv untuk dua proses budget SQLite terisolasi. Tidak ada route produksi baru. |

Routes, middleware role, guard `WslDevelopmentIdentity.php`, perhitungan IPS/IPK, model/struktur histori analisis, dan source JavaScript tidak diubah. SDK, Redis wajib, cron, Supervisor dan queue worker tidak ditambahkan.

## Policy final dan readiness

**Local:** `WslDevelopmentIdentity::verify()` tetap wajib dengan seluruh validasi existing: environment local, SQLite, DB URL kosong, manifest/tag/path, file nyata bukan symlink dan PRAGMA identitas koneksi. ID mahasiswa harus berada pada allowlist sintetis manifest. Local yang gagal tidak mencoba jalur production.

**Production demo:** hanya environment `production`, enabled boolean true, mode persis `production-demo`, environment termasuk allowlist server, debug false, APP_URL HTTPS, cookie Secure/HttpOnly, session/cache persisten yang diizinkan, dan user ID termasuk allowlist. Driver yang diizinkan mysql/mariadb. DB URL tidak boleh mengganti konfigurasi; nama DB konfigurasi harus cocok dengan nama demo eksplisit, dan `SELECT DATABASE()` pada koneksi aktual juga harus sama. Batas global dan user harus integer 1–10.000. Key atau APP_ENV production saja tidak mengaktifkan fitur. Semua environment lain ditolak.

Staging nantinya memakai instalasi terpisah dengan `APP_ENV=production` dan database demo sendiri, agar policy yang diuji sama dengan target. Environment bernama `staging` tidak mempunyai bypass.

**Testing:** jalur mock existing tetap tersedia dan client mengaktifkan `Http::preventStrayRequests()`. Tes jalur penyimpanan production menggunakan substitusi policy khusus di kode tes dengan seluruh HTTP fake; bukan flag bypass dalam aplikasi. Tes penerimaan konfigurasi production memock identitas koneksi database, sehingga bukan sertifikasi MySQL.

UI memanggil `readiness()` yang memakai pemeriksaan akses/provider yang sama. Backend tetap melakukan pengecekan ulang untuk POST. UI menampilkan pesan konfigurasi/izin, batas permintaan, atau gangguan sementara tanpa menyertakan key/path/SQL. State siap memerlukan pilihan semester dan consent; loading dan pemulihan tombol melalui pageshow tetap memakai JavaScript existing. Kuota tidak menyebabkan tombol terkunci permanen: refresh mengevaluasi ulang setelah window/lease berakhir. Batas lifetime memerlukan keputusan pengelola.

Readiness adalah snapshot saat GET, bukan jaminan kapasitas pada saat klik berikutnya; request bersamaan/perubahan konfigurasi dapat membuat POST ditolak dengan pesan aman. Riwayat GET selalu independen dari flag/provider. POST reuse tetap memerlukan policy akses, tetapi live flag OFF atau kuota habis tidak memaksa HTTP baru bila hasil matching sudah tersedia; tombol untuk analisis baru tetap disabled ketika OFF.

## Konfigurasi baru dan staging nanti

Semua default production-demo nonaktif. Variabel baru:

| Variabel | Default | Makna |
| --- | --- | --- |
| `GROQ_DEMO_ENABLED` | false | Opt-in terpisah dari flag live existing. |
| `GROQ_DEMO_MODE` | kosong | Harus persis `production-demo`. |
| `GROQ_DEMO_ENVIRONMENTS` | kosong | CSV environment; gunakan `production` pada instalasi staging/demo terpisah. |
| `GROQ_DEMO_DATABASE` | kosong | Nama DB demo yang sengaja dipilih, harus cocok konfigurasi dan koneksi aktual. |
| `GROQ_DEMO_USER_IDS` | kosong | CSV ID user mahasiswa allowlisted, bukan ID profil mahasiswa. |
| `GROQ_DEMO_GLOBAL_LIMIT` | 0 | Maksimal reservasi attempt global selama umur tabel. |
| `GROQ_DEMO_USER_LIMIT` | 0 | Maksimal reservasi attempt/user selama umur tabel. |

`GROQ_LIVE_ENABLED` tetap default false. Provider tetap endpoint Groq fixed dengan model `openai/gpt-oss-20b`; policy dan client kini sepakat tentang model ini. `services.groq.mocked` tidak lagi merupakan override provenance runtime; testing menghasilkan mock, runtime non-testing menghasilkan live.

Persiapan staging berikut belum dijalankan:

1. Pilih hosting PHP ≥8.2 dengan ext-curl, OpenSSL/CA valid, mbstring, pdo_mysql dan extension Laravel sesuai lockfile; document root `/public`, source/config/cache privat dan writable storage yang benar.
2. Siapkan database demo kosong khusus, dataset sintetis terkurasi dan akun mahasiswa allowlisted. ID allowlist bukan bukti sintetis: review data tetap wajib. Jangan membawa dump database utama, manifest/credential WSL atau artefak QA. Akun privileged harus memakai password aman dan akses terbatas.
3. Rehearsal seluruh migration existing dan migration budget baru pada engine/versi target. Pastikan tabel budget mempunyai baris global ID 1; baris yang hilang menolak request, bukan dibuat ulang otomatis untuk mengembalikan kuota.
4. Set production/debug false, URL HTTPS, APP_KEY instalasi demo yang kemudian dipertahankan, secure/HttpOnly cookie, serta session/cache file atau database yang writable/persisten. Driver file/database paling sederhana; Redis/Memcached hanya jika memang disediakan dan diuji.
5. Isi mode/environment/nama DB/ID akun dan batas konservatif sesuai izin pengelola. Jangan berikan izin live ke seluruh akun secara otomatis. Tidak ada endpoint untuk mereset kuota.
6. Simpan key hanya pada konfigurasi server privat; jangan ke `VITE_*`, JS, HTML, repository, paket publik atau log. Cache konfigurasi Laravel juga privat. Jangan salin `.env` development.
7. Tetap live OFF sampai mock/staging, timeout, TLS/outbound dan kuota diverifikasi. Config cache harus diperbarui secara terkontrol saat flag berubah. Tidak ada perintah aktivasi yang dijalankan dalam pekerjaan ini.
8. Smoke live memerlukan persetujuan terpisah dengan batas attempt, model, akun/dataset dan anggaran yang jelas. Rekomendasi awal satu attempt, tanpa retry otomatis. Tidak ada request models/healthcheck Groq tambahan.

## Provenance dan histori

Reuse memerlukan mahasiswa, semester, sumber `groq_deskriptif`, fingerprint indikator/model/metode, **dan transport yang sesuai**. Di runtime live, hasil mock/legacy tanpa transport tidak menggantikan hasil live. Di testing, mock tidak mengambil histori live. Mock lama tidak dihapus atau diedit.

Analisis baru tetap append-only, dengan snapshot agregat, model/metode `descriptive-v2`, penjelasan, usage tervalidasi, transport dan timestamp. Skor/kategori tetap null; tidak ada klaim skor risiko resmi. Rekomendasi dibuat dalam transaksi yang sama. Provenance ditentukan backend, tidak dari form atau label visual.

Recheck reuse setelah reservasi mencegah HTTP duplikat bila request lain selesai antara lookup pertama dan reservasi global. Tidak ada tombol paksa regenerasi atau retry provider tersembunyi. Membuka dashboard/filter/histori/Owner tidak mengirim HTTP provider.

## Kuota, concurrency dan kegagalan parsial

- Tabel global `groq_demo_budgets` menyimpan attempts dan lease; `groq_demo_user_budgets` menyimpan lifetime attempts dan window dua/menit/user. Tidak memakai FK cascade pada budget user agar penghapusan akun tidak mengembalikan attempt.
- Transaksi singkat mengunci baris global tetap dengan `lockForUpdate`, memeriksa semua batas, lalu mengubah counter user/global dan lease atomik. Pemeriksaan/increment user dilakukan di bawah mutex global yang sama. Tidak menahan transaksi database selama HTTP.
- Satu request global berjalan pada lease 120 detik. Owner token acak mencegah request lama melepas lease baru. Jika proses mati, lease kedaluwarsa; counter tetap terpakai. Saat penyimpanan, token dan masa lease diverifikasi lagi di bawah row lock; hasil terlambat/lease hilang ditolak.
- Lock mahasiswa/semester existing 90 detik dan route throttle 6/menit tetap berlaku. Rate limiter cache dua/menit tetap merupakan proteksi tambahan; batas lifetime dan minute-window production juga persisten di database.
- Cache clear, refresh, retry atau perubahan ID periode di konfigurasi tidak menghapus counter. Tidak ada reset harian otomatis. Limit dapat disesuaikan hanya pengelola server; jangan rollback migration budget untuk mengakali kuota.
- Reservasi dikonsumsi sebelum HTTP, tidak direfund saat provider 429/5xx, timeout, crash, validasi gagal, data berubah, atau penyimpanan gagal. Ini batas atas konservatif, bukan meter tagihan: proses yang mati sebelum transport atau menemukan reuse pada recheck bisa mengonsumsi reservasi tanpa HTTP. Tidak mengklaim semua reservasi adalah panggilan berbayar.
- Lease mengasumsikan durasi normal lebih pendek dari 120 detik dan clock server konsisten. Proses yang tertahan di luar timeout hosting dapat bertumpang tindih setelah lease kedaluwarsa, tetapi setiap reservasi tetap dibatasi budget dan pemilik lease lama tidak dapat menyimpan hasil terlambat. Exactly-once billing tidak dapat dijamin bila hasil jaringan tidak pasti; jangan retry otomatis.

Client existing tetap connect timeout 5 detik, total 25 detik, tanpa redirect/retry, TLS verification aktif. Error provider/timeout/format invalid menjadi pesan aman. SQL gagal saat insert parent/child membatalkan transaksi dan menampilkan bahwa hasil baru tidak tersimpan, dengan instruksi memeriksa histori sebelum mencoba lagi. Controller menangani gangguan runtime dengan pesan umum tanpa raw exception. Tidak menambahkan logging header/token/prompt/body provider.

## Pengujian yang benar-benar dilakukan

PHP native tidak tersedia. Runtime PHP 8.2.34 existing dipakai melalui `docker exec`; QA root `/tmp/uninalar-groq-production-qa` dibuat tanpa `.env`, credential, manifest privat atau DB development. PHPUnit memakai SQLite `:memory:` yang diverifikasi oleh `Tests\TestCase`. Uji dua proses memakai SQLite baru bernama acak di `/tmp`, hanya tabel budget, lalu dibersihkan. Tidak memakai server MySQL operasional.

| Pemeriksaan | Hasil / batas |
| --- | --- |
| Groq existing + production-demo + presentasi analisis + ringkasan nilai + workflow KRS | **41 tes / 302 assertions PASS** pada putaran final source aplikasi; `qa-artifacts/groq-production/phpunit-final.log`. |
| Tes production-demo setelah penambahan skenario recheck race terakhir | **13 tes / 81 assertions PASS**; `production-final.log`. Tumpang tindih dengan suite di atas, jangan menjumlahkan keduanya sebagai jumlah tes unik. |
| Dua proses nyata melakukan reservasi budget bersamaan | PASS: satu reserved, satu blocked, attempts global 1. SQLite terisolasi; **bukan bukti locking MySQL/MariaDB**. |
| Regresi Student/Mahasiswa, rekomendasi, Owner, keamanan, operasi akademik dan Dosen | **65 tes / 1.319 assertions, 2 failures**, sehingga 63 tes lulus; `regression.log`. Kedua failure baseline dijelaskan di bawah. |
| Pembanding source murni HEAD `fca168c` | Dua tes yang sama juga gagal: **2 tes / 13 assertions, 2 failures**, `baseline-regression.log`. Root `/tmp/uninalar-groq-baseline-qa`, tanpa dotenv/DB utama, autoload diarahkan ke source HEAD. |
| Pint | **PASS 9 file PHP** yang berubah/ditambah. |
| PHP syntax | **PASS 9 file PHP** yang berubah/ditambah. |
| Blade compilation | PASS, `view:cache` hanya pada root QA dan storage QA. |
| Vite production build | PASS **3,08 detik**, dependency existing; tidak ada install/download. |
| `git diff --check` | PASS; file untracked baru juga diperiksa whitespace terpisah. |
| MySQL/MariaDB baru | **NOT VERIFIED/BLOCKED**: tidak ada instance QA terisolasi existing yang tersedia dan terverifikasi. Tidak membuat/mengubah Docker demi pengujian ini. |
| Groq live / hosting / outbound nyata | **NOT RUN**, memerlukan staging dan persetujuan live terpisah. |

Tes production-demo mencakup default OFF, config/allowlist/environment/driver tidak lengkap, identitas DB aktual yang dimock, guard local gagal tanpa fallback, UI/backend denied yang konsisten, mock-vs-live reuse, histori tetap identik, batas persisten/cache clear, lease/owner token/expiry, provider failure, parent-child rollback, budget row hilang dan race lookup/reservation. Suite Groq existing mencakup authorization lintas role/mahasiswa, consent, tanpa PII, timeout, provider error, invalid output, double-submit/lock dan navigasi tanpa HTTP. Regresi ringkasan nilai/KRS termasuk pada suite lulus di atas.

**Dua kegagalan baseline yang tidak diperbaiki di luar scope:**

1. `AcademicOperationsTest::test_admin_class_crud_search_detail_and_krs_integration`, baris 66: mengharapkan teks persis `Data operasional belum tersedia.` pada UI yang sudah direvisi.
2. `AcademicOperationsTest::test_owner_and_lecturer_dashboards_aggregate_database_values_and_filter`, baris 185: mengharapkan teks huruf kecil `direkomendasikan` pada UI Owner. Assertion agregat sebelumnya lulus; assertion teks tidak cocok tampilan checkpoint.

Tidak mengubah UI/tes tersebut untuk menyembunyikan failure. Keseluruhan regresi **tidak diklaim seluruhnya PASS**. Percobaan QA awal juga menemukan masalah harness copy/bootstrap dan teardown mock yang sudah diperbaiki; hasil awal tidak dipakai sebagai pass.

## Pemeriksaan browser yang benar-benar dilakukan

Playwright MCP dan Chrome DevTools MCP digunakan pada server QA lokal `127.0.0.1:8142`. Server hanya melayani **HTML hasil render aplikasi pada fixture SQLite memory** dan aset lokal; seluruh POST ditolak. Ini pemeriksaan UI terarah, bukan login/write end-to-end pada DB development atau bukti HTTP provider. Link font eksternal di snapshot QA dihapus agar pemeriksaan final memakai aset lokal saja; source layout tidak diubah.

- Playwright, viewport **1280×720**: state siap; sebelum semester/consent tombol disabled, sesudah input valid enabled; dispatch submit mengaktifkan spinner/busy; pageshow memulihkan tombol. State live OFF menampilkan pesan jelas dan tombol disabled.
- Chrome DevTools, viewport **390×844**: state batas permintaan disabled dengan pesan tunggu satu menit; tidak ada overflow dokumen atau gambar rusak. Screenshot mobile ditinjau melalui tool, tanpa credential/halaman login. Penyimpanan screenshot ke path workspace ditolak oleh batas filesystem MCP, sehingga tidak mengklaim file screenshot lokal tersimpan.
- Putaran final: tidak ada console error/warning pada halaman quota; tidak ada XHR/fetch atau resource Groq pada state yang diperiksa. Snapshot disabled Playwright juga tidak memuat resource eksternal. Ini melengkapi assertion backend `Http::assertNothingSent()`, bukan menggantikan bukti jumlah request backend.
- Pemeriksaan awal harness memiliki path gambar 404 dan kesalahan server QA sementara; diperbaiki pada harness, tanpa perubahan source produk. Server QA dihentikan setelah pemeriksaan.

## Integritas data dan dampak development

Sebelum pekerjaan, hash opaque empat file terlindungi dicatat: `.env`, file SQLite development, manifest runtime privat, dan ledger live pertama. Sesudah pengujian, **4/4 byte-identical**, himpunan file SQLite/sidecar yang dipantau tidak bertambah, dan **0 file terlindungi berubah**. Ringkasan tanpa isi/hash rahasia tersimpan pada `qa-artifacts/groq-production/integrity.json`.

Identitas dan byte database tetap sama, sehingga data Hendrik/Jovian, akun, KRS, nilai/kehadiran dan histori Groq dalam file tersebut tidak berubah. Tidak ada koneksi ke MySQL operasional. Ini bukti integritas file lokal yang diperiksa, bukan audit isi server operasional yang tidak diakses.

Guard WSL, internal role Owner dan semua route mutatif akademik tidak berubah. Production-demo default OFF; nilai flag development yang sudah ada tidak diubah. Pengetatan reuse ke asal transport yang benar merupakan perubahan yang disengaja: hasil mock tidak lagi menghalangi generasi live pertama. Tidak ada generasi baru pada DB utama selama pekerjaan ini.

Artefak QA, snapshot HTML, baseline tar, log, harness, dan hasil browser tetap ignored dan **bukan paket release**. Jangan ikutkan `.agents/`, `.playwright-mcp/`, credential, log, manifest privat atau QA artifacts saat membuat checkpoint/release. Source baru policy/budget/migration/tes diperlukan untuk meninjau implementasi; tidak ada Git add/commit/push yang dilakukan.

## Shared hosting: hal yang tetap NOT VERIFIED

1. Seluruh migration pada MySQL/MariaDB target, khususnya nullable skor/kategori, JSON path transport/fingerprint, FK, dan locking budget lintas proses. Migration role CHECK existing mempunyai perbedaan driver/sintaks MariaDB yang tetap perlu rehearsal.
2. Outbound DNS/HTTPS 443 ke Groq, CA/TLS, ext-curl dan dukungan parameter/model pada akun sebenarnya. Jangan menonaktifkan TLS verification.
3. Timeout PHP/FPM/LiteSpeed/proxy di atas 25 detik plus overhead query/write. Konfirmasi anggaran praktis 45–60 detik dan kapasitas worker; label paket murah bukan bukti kapasitas.
4. Permission storage/cache, session persisten dan lock lintas request, document root, HTTPS/proxy, private config cache, backup dan restore demo. File cache mengasumsikan server tunggal; topologi multinode perlu storage/lock bersama yang diuji.
5. Dataset/account initializer hosting, pembatasan akses evaluator dan anggaran provider. Kuota aplikasi adalah batas jumlah reservasi, bukan jaminan nominal tagihan.

## Rollback

Matikan `GROQ_LIVE_ENABLED` dan `GROQ_DEMO_ENABLED` pada server target melalui prosedur konfigurasi/cache yang aman; ini rencana nanti, bukan tindakan sesi ini. Request yang sudah berjalan mungkin masih selesai. GET histori tetap tersedia. Jangan mereset kuota, menghapus hasil atau mencoba request berbayar ulang hanya karena browser timeout.

Bila source perlu dikembalikan, gunakan release sebelumnya yang kompatibel dengan schema nullable existing dan biarkan tabel budget/data tersimpan. Jangan menjalankan migration down untuk mengaktifkan kembali kuota; jangan rollback migration deskriptif bila sudah ada skor/kategori null. Tidak perlu mengubah APP_KEY atau data WSL. Jika ada indikasi kebocoran key, penonaktifan/revoke/rotasi provider merupakan tindakan terpisah yang harus disetujui.

**Gerbang berikut:** review perubahan → rehearsal DB target dan staging mock → verifikasi hosting/outbound → persetujuan smoke live terbatas. Implementasi lokal selesai dengan batas verifikasi di atas; live production belum diaktifkan.
