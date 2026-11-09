# UniNalar — final pre-deployment preparation

Tanggal pemeriksaan: 9 November 2026. Scope terakhir pengguna adalah **persiapan lokal**, bukan deployment. Tidak ada hosting/domain yang dibeli, DNS diubah, aplikasi diunggah, atau Groq live diaktifkan/dipanggil.

## Keputusan

**CONDITIONAL GO untuk persiapan deployment demo sekolah.** Source, template, paket frontend/vendor, initializer sintetis dan prosedur lokal tersedia. **Belum FINAL GO untuk website publik atau Groq live**: runtime provider, HTTPS, akses privat, database target, backup/restore, E2E online dan outbound API masih **PENDING HOSTING**.

Tidak ditemukan blocker baru pada pemeriksaan lokal yang dijalankan. Kompatibilitas shared hosting tertentu tidak dapat disimpulkan dari kelulusan localhost/MySQL QA. Laporan ini memperbarui status persiapan pada laporan audit lama; hasil QA lama tetap merupakan bukti historis dengan cakupannya sendiri.

| Phase | Status dalam scope persiapan | Bukti / batas |
| --- | --- | --- |
| 0 — preflight | PASS dengan batas integritas di bawah | HEAD/source/runtime/konfigurasi diperiksa; hash awal tidak tersedia kembali |
| 1 — kebutuhan hosting | PASS | Spesifikasi dan kriteria pembelian tersedia; pemilihan/verifikasi paket PENDING HOSTING |
| 2 — production package | PASS | Build, vendor no-dev, platform/cache/smoke/arsip lokal lulus |
| 3 — database dan akun | PASS di QA | Migration dan initializer diuji SQLite memory/MySQL baru; akun hosting belum dibuat |
| 4 — Groq readiness | PASS untuk konfigurasi OFF | Policy existing dipertahankan; konektivitas/live hosting PENDING HOSTING |
| 5 — deployment dan browser online | PENDING HOSTING | Tidak dilakukan sesuai scope; belum ada URL publik |
| 6 — security acceptance | PASS untuk persiapan; penerimaan online PENDING HOSTING | Template/arsip/guard diperiksa; keamanan server belum terbukti |
| 7 — handoff | PASS | Runbook, checklist dan laporan ini tersedia |

## Baseline dan perubahan

Branch `main`, HEAD `cee8900`. Persiapan baru belum di-commit; tidak ada Git add/commit/push/reset/clean. `.agents/` sudah untracked sebelum pekerjaan dan tidak dimasukkan ke release.

Perubahan hanya mendukung persiapan dan CLI, tanpa perubahan UI atau aturan akademik existing:

- `.env.production.example`: template tanpa credential, HTTPS/debug false/file session dan cache, provisioning serta kedua mode Groq OFF.
- `.env.example`: menambahkan tiga opt-in provisioning yang default OFF/kosong; **bukan** `.env` development.
- `config/hosting_demo.php`, `app/Services/HostingDemoProvisioner.php`: guard database baru dan dataset sintetis transaksional, menolak overwrite.
- `app/Console/Commands/PrepareHostingDemo.php`: empat password acak 32 karakter, hash DB, file privat baru 0600 di direktori 0700 di luar aplikasi/public; password tidak dicetak atau dijadikan argumen.
- `app/Console/Commands/RotateHostingDemoPassword.php`: pergantian password empat akun fixture melalui hidden input, validasi identitas DB/akun; tidak membuka endpoint web baru.
- `scripts/prepare-shared-hosting-release.py`: staging allowlist dan arsip lokal; menolak dotenv, database, cache mesin, log, symlink dan vendor dev. Mencakup `lang/id/validation.php`.
- Test provisioning SQLite/dedicated MySQL, trait skenario bersama dan `phpunit.hosting-demo-mysql.xml`. Suite MySQL baru dipisahkan dari suite existing agar database kosong menjadi prasyarat yang nyata.
- `docs/shared-hosting-installation.md` dan `docs/shared-hosting-online-checklist.md`.

## Runtime dan kebutuhan hosting

Terbukti lokal: Laravel **12.70.0**, PHP **8.2.34**, Composer **2.10.3**, Node **24.21.0**, Vite **7.3.7**, MySQL QA **8.4.11**. PHP/Composer menggunakan image lokal yang sudah tersedia; tidak membutuhkan pemasangan PHP/dependency baru pada WSL. Composer platform check package runtime lulus pada PHP tersebut.

Minimum pembelian yang harus dikonfirmasi pada akun sebenarnya:

- PHP >=8.2 kompatibel lockfile, web dan CLI; ctype, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl, pcre, session, tokenizer, PDO/pdo_mysql. Untuk HTTP Groq pilih curl dan CA TLS valid.
- MySQL/InnoDB dengan transaction, FK/unique/JSON/row lock; **hanya MySQL 8.4.11 yang diuji di QA ini**. MariaDB dan versi provider lain NOT VERIFIED.
- Document root `public/`, source/config/vendor/storage privat di luar web root, HTTPS dan rewrite, storage/bootstrap cache writable sesuai UID PHP tanpa 0777.
- Jalur Artisan aman: SSH/Terminal, bantuan provider atau cron CLI sementara. Composer server tidak wajib karena vendor dibundel. Tanpa jalur CLI aman atau tanpa root publik yang aman: **NO-GO untuk paket itu**.
- Perencanaan awal PHP memory 128 MB, lebih nyaman 256 MB, storage 1 GB. Bukan hasil benchmark hosting; cek inode, CPU, entry-process, kuota DB, backup dan biaya renewal.
- Request Groq sinkron: outbound HTTPS 443 ke api.groq.com dan timeout efektif melebihi 25 detik + DB/write; tanyakan anggaran awal 45–60 detik lalu ukur. Tidak perlu Node server, Docker, Redis, Supervisor atau worker permanen pada konfigurasi demo ini.

Tidak ada provider dipilih atau harga pembelian yang dinyatakan terverifikasi. Evaluasi paket/budget/periode dilakukan setelah pengguna memutuskan membeli. Pemeriksaan halaman publik provider sebelumnya tidak membuktikan kemampuan akun hosting aktual.

## Bukti pengujian lokal

Artefak mentah lokal berada di `qa-artifacts/final-predeploy/`, **bukan untuk diunggah seluruhnya**.

| Pemeriksaan | Hasil |
| --- | --- |
| Test initializer/CLI SQLite in-memory, final | **9 tests / 73 assertions PASS** (`provision-sqlite-final.log`) |
| Test initializer production policy MySQL 8.4.11 baru, final | **5 tests / 37 assertions PASS** (`mysql/provisioning-final.log`) |
| Regression lokal terkait | **42 tests / 365 assertions PASS** (`local-regression.log`); sebagian mencakup provisioning, jangan dijumlah sebagai cakupan unik |
| Seluruh migration pada MySQL QA baru | **10 migration PASS** (`mysql/migrate.log`) |
| Smoke production vendor tanpa dev/Faker | Migration + initializer + empat akun + IPS/IPK awal **3,50 PASS** |
| PHP syntax / Pint | **7 file PASS** |
| Vite production build | **PASS**, tanpa install/download |
| Composer runtime platform requirements | **PASS** pada PHP lokal 8.2.34 |
| package:discover, config:cache, route:cache, view:cache, optimize:clear | **PASS** pada salinan release terisolasi, bukan konfigurasi hosting |
| Asset manifest / ZIP CRC | Semua file referensi ada; **PASS** |
| git diff --check | **PASS** |

Initializer membuktikan hash password empat role, fixture terlihat pada query operasional, status mock yang jujur, penolakan rerun/nonempty DB/weak password/local environment dan Groq ON. CLI membuktikan file privat, penolakan overwrite/path dalam aplikasi, atomic rollback pada kegagalan SQL serta pemeliharaan credential untuk recovery. Alur KRS aktif draft → submit → approve → finalisasi melalui service existing menghasilkan IPS **4,00** dan IPK **3,63** pada QA.

Bukti existing yang digunakan kembali sesuai larangan mengulang tes berat tanpa dampak: regression SQLite **110/1.709**, MySQL QA **69/1.248**, production-demo tambahan **1/21**, termasuk concurrency dalam cakupannya. Itu bukan eksekusi baru tahap ini dan bukan bukti browser pada hosting. Login/logout/authorization/CRUD/kehadiran/nilai/monitoring empat role tetap membutuhkan checklist E2E online.

QA MySQL baru memakai image lokal `--pull=never`, jaringan internal baru, tanpa port publik, database tmpfs baru dan source salinan tanpa `.env`. Migration/test hanya menuju schema `qa_hosting_75c4473a87b9`. Resource QA baru sudah dihapus dengan cleanup khusus run; container/database operasional tidak diakses. SQLite RefreshDatabase hanya pada `:memory:` di salinan QA, tidak pada database development.

## Paket production

Arsip final: `qa-artifacts/final-predeploy/uninalar-predeployment-release.zip`.

- Ukuran **9.584.979 byte**; ZIP CRC lulus.
- SHA-256: `75865c79b4ba810b884df94cd563e78e02cf32aad8e8e0b8b1f8a6f13d61f976`.
- **77 package runtime**, metadata Composer dev=false dan daftar dev kosong. Composer dijalankan offline hanya pada staging: 0 install/0 update, 35 package dev dibuang dari salinan, vendor development tidak dipangkas.
- Composer advisory metadata tidak dapat diperbarui dalam mode offline: status advisory terkini **NOT VERIFIED**. Kelulusan platform check bukan audit vulnerability daring.
- Memuat source, migration, terjemahan, static public/build, vendor, template, `DEPLOYMENT.md` dan `ONLINE-CHECKLIST.md`. `release-source.json` menandai HEAD serta working tree yang disertakan.
- Tidak ada `.env` aktual, SQLite/SQL dump, secret key file, log, Git, browser profile, `.agents`, test/QA script atau development seeder/factory. Cache/bootstrap/view/session hasil verifikasi dibersihkan pada staging sebelum arsip.
- Scan signature key Groq berentropi tinggi/private-key PEM pada staging/public: **0 file**. Ini scan terbatas, bukan jaminan deteksi semua bentuk secret.
- `public/hot` tidak dibawa. Semua referensi manifest tersedia; build membutuhkan Node hanya saat dibangun lokal.

`release.zip` sebelumnya adalah paket sementara sebelum tambahan terjemahan; gunakan **hanya nama arsip final di atas**. Artefak ignored bukan commit Git. Bangun ulang paket dan checksum bila source/dependency berubah; laporan final ini tetap tersedia di repository docs.

## Database dan akun demo

Jangan salin DB WSL atau gunakan DatabaseSeeder development untuk hosting. Initializer baru hanya untuk DB demo baru dengan opt-in eksplisit, nama target cocok konfigurasi/konfirmasi CLI/SELECT DATABASE(), production/debug false/HTTPS, Groq OFF dan tabel domain kosong. Tidak menghapus existing record/history/budget. Transaction mengunci budget global; rerun gagal tertutup.

Empat username: `demo_mahasiswa`, `demo_dosen`, `demo_admin`, `demo_pimpinan`. Password tidak ditanam dalam source: dibangkitkan per instalasi, berbeda, disimpan hash dan file privat eksklusif. Akun tersebut **baru dibuat di QA**, belum ada akun hosting. Kredensial admin/pimpinan diberikan hanya kepada evaluator berwenang, bukan pengunjung umum.

Dataset: 1 prodi, 1 mahasiswa, 1 dosen, 2 semester, 6 mata kuliah/kelas, KRS historis disetujui dan KRS aktif draft, 3 nilai final historis, 12 kehadiran, history keputusan, 1 analisis/rekomendasi fixture. Provenance `hosting_demo_fixture`/mock, bukan hasil Groq. Jendela KRS aktif relatif waktu provisioning dan berakhir 14 hari kemudian. Analisis semester aktif memerlukan persetujuan KRS dahulu sesuai aturan existing.

Password rotation melalui CLI tersembunyi; remember_token dibatalkan. Untuk mengakhiri sesi file existing, ubah nama SESSION_COOKIE hosting dan cache ulang. Jangan menyimpulkan perubahan password saja memutus semua sesi. Jika commit provisioning belum pasti, simpan file credentials untuk pemeriksaan dan jangan retry otomatis.

## Groq dan keamanan minimum

Production-demo dan template tetap **default OFF**: `GROQ_LIVE_ENABLED=false`, `GROQ_DEMO_ENABLED=false`, mode/env/database/user allowlist kosong dan limit 0. `.env` development tidak disunting; laporan ini tidak mengklaim semua flag lokal historis dimatikan.

Policy WSL existing tidak dihapus. Aktivasi nanti memerlukan mode persis `production-demo`, environment production, DB demo yang sesuai, ID **user mahasiswa** allowlist, limit global/per-user 1–10.000 yang konservatif, HTTPS/secure session, debug false dan provisioning OFF. Key hanya konfigurasi privat server; request backend Laravel sinkron, bukan JavaScript. Model/base URL mengikuti policy source (`openai/gpt-oss-20b`, `https://api.groq.com/openai/v1`).

Budget persisten/locking dan lease global existing dipertahankan, timeout HTTP 25 detik; reservasi gagal/timeout tidak otomatis dikembalikan. Tidak ada retry berbayar otomatis. Histori tersimpan dapat dibaca tanpa request provider; fixture mock tidak dianggap hasil live. Rate limit, scope data mahasiswa sendiri dan safe error existing tidak dilonggarkan.

Template mengatur file session/cache persisten, cookie Secure/HttpOnly/Lax, enkripsi session, log warning harian, filesystem privat dan queue sync. Route/UI akademik tidak diubah. Endpoint debug/installer tidak ditambahkan. Secret baru tidak dibuat untuk deployment; APP_KEY baru hanya nanti pada instalasi hosting baru yang disetujui. Runtime user DB sebaiknya DML pada schema demo saja, DDL terpisah untuk migration bila provider mendukung.

## Integritas dan batas verifikasi

Tidak ada perintah yang dimaksudkan menulis `.env`, APP_KEY WSL, database development atau ledger/history Groq. Tidak ada koneksi ke MySQL operasional dan tidak ada request Groq live. Source/test provisioning dieksekusi pada QA terisolasi; cache/Composer hanya pada salinan release.

**NOT VERIFIED: kesamaan byte sebelum/sesudah file terlindungi.** Baseline hash awal yang disebut dalam catatan kerja sementara tidak ditemukan kembali pada pemeriksaan akhir, sehingga klaim identik untuk `.env`, SQLite development, runtime manifest dan ledger tidak dapat dituntaskan dengan bukti hash. Tidak dilakukan pembacaan nilai credential atau reset data untuk merekonstruksi baseline. HEAD tetap `cee8900`. Fingerprint seluruh DB MySQL operasional juga tidak tersedia karena aksesnya memang dilarang. Bukti isolasi QA tidak diganti dengan klaim kesamaan database yang tidak terukur.

## Blocker, required dan optional

**BLOCKER untuk publikasi, PENDING HOSTING:** provider belum tersedia/terverifikasi, belum ada URL HTTPS, root privat/permission/versi PHP-DB/backup restore belum diuji aktual. Jika provider gagal memenuhi root publik dan CLI aman, pilih provider lain. Untuk live tambahan: outbound TLS, hard timeout, key server, budget dan persetujuan satu attempt belum tersedia. MariaDB/versi DB selain yang diuji memerlukan QA sesuai target.

**REQUIRED sebelum upload:** pilih paket berdasarkan checklist, sepakati biaya/renewal/periode; siapkan DB demo baru, deployment key/credential privat, release checksum, backup dan jalur Artisan. Isi template pada hosting, migrate sekali setelah verifikasi target, initializer sekali setelah persetujuan, matikan provisioning, bangun cache pada target dan jalankan seluruh checklist online. Perbarui bukti integritas lokal jika diperlukan sebelum pekerjaan berikutnya; jangan mengklaim baseline lama tersedia.

**OPTIONAL:** deployment berbasis symlink release bila didukung, DB user migration terpisah dari runtime, maintenance access restriction untuk evaluator, resource lebih besar hanya bila pengukuran demo membutuhkannya. Tidak perlu membeli Redis/worker/Node hosting untuk konfigurasi ini.

## Instalasi, backup, rollback dan langkah berikut

Prosedur lengkap SSH maupun tanpa SSH ada di [shared-hosting-installation.md](shared-hosting-installation.md). Checklist penerimaan ada di [shared-hosting-online-checklist.md](shared-hosting-online-checklist.md). Tanpa SSH gunakan vendor/build lokal dan Terminal/provider/cron CLI sementara; **tidak membuat installer publik**.

Backup privat mencakup release/checksum, dump DB konsisten, APP_KEY dan konfigurasi; tidak diletakkan di public_html. Restore wajib diuji pada DB QA target. Rollback: live OFF, cache diperbarui, akses dibatasi bila perlu, pulihkan release kompatibel atau forward fix setelah backup. Jangan migrate:fresh/down otomatis, menghapus histori/counter, atau mengganti APP_KEY instalasi existing. Prosedur backup provider sendiri masih PENDING HOSTING.

Kompleksitas persiapan lokal selesai dan dapat diulang tanpa dependency baru selama cache/vendor lokal tersedia. Instalasi server bergantung panel/root/CLI/versi DB; live AI memerlukan checkpoint tersendiri, sehingga waktu dan biaya aktual belum dapat dipastikan. Tahap berikut cukup memilih hosting yang memenuhi checklist, mengonfirmasi akses/target/persetujuan, mengunggah paket, mengisi konfigurasi privat dan menyelesaikan pemeriksaan online. Tidak ada tindakan eksternal dilakukan pada tahap ini.
