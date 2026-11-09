# Kesiapan deployment demo sekolah UniNalar

Audit: 9 November 2026, Asia/Jakarta. Workspace `/home/hendra_huang/projects/UniNalar-main`, branch `main`, checkpoint **`fca168c`**.

## 1. Keputusan

**CONDITIONAL GO untuk menyiapkan demo sekolah pada shared hosting, dengan Groq live dinonaktifkan dan hasil analisis tersimpan sebagai bahan demonstrasi. Belum GO untuk langsung mengunggah salinan development dan membukanya ke publik.**

Alur akademik utama telah memiliki bukti QA lokal. Aplikasi tidak memerlukan Docker, WSL, Node server atau queue worker permanen untuk melayani alur HTTP existing. Shared hosting PHP/MySQL sederhana dapat menjadi pilihan, asalkan document root, runtime, akses CLI terbatas, permission dan database benar-benar sesuai.

**NO-GO untuk menjanjikan analisis Groq live di hosting dengan source sekarang.** UI membatasi analisis baru pada local/testing; backend juga memverifikasi SQLite development, path manifest dan daftar mahasiswa sintetis sebelum memproses generate maupun reuse melalui POST. Mengisi API key dan flag saja tidak mengatasi batas ini. Jangan mengakalinya dengan `APP_ENV=local`/`testing` di hosting.

Belum ada provider/paket/domain target, dan tidak ada pengujian pada server hosting. Audit ini memakai source, lockfile dan artefak lokal, tanpa pencarian harga/provider atau akses website eksternal.

## 2. Gerbang sebelum deployment

BLOCKER berarti harus diselesaikan sebelum publikasi sesuai cakupan yang dipilih; REQUIRED adalah persiapan wajib; OPTIONAL tidak menghalangi demo terbatas.

| Kategori | Temuan/keputusan yang diperlukan |
|---|---|
| **BLOCKER B1 — konfigurasi publik** | `.env` development masih local, debug aktif, URL localhost, SQLite, dan flag Groq live aktif untuk pengujian lokal. Jangan menyalinnya ke hosting. Siapkan environment baru yang aman, APP_KEY khusus instalasi demo baru, HTTPS dan cookie Secure. Audit tidak mengubah nilai apa pun. |
| **BLOCKER B2 — akun dan data demo** | Provisioning utama saat ini bergantung pada development terverifikasi. `DatabaseSeeder` tidak menyiapkan akun pada production; `AcademicDemoSeeder` menolak production. Belum ada initializer demo hosting yang sesuai. Harus ada paket data sintetis dan empat akun yang direview sebelum demo dapat digunakan. |
| **BLOCKER B3 — password publik** | Fixture development menggunakan password tetap/mudah ditebak. Jangan memublikasikan akun berhak Admin/Dosen dengan password development. Buat password berbeda yang kuat, bagikan terbatas kepada penguji, dan pertimbangkan proteksi akses tingkat hosting selama penilaian. Nilai password sengaja tidak ditampilkan. |
| **BLOCKER B4 — release dan web root** | Harus dapat melayani hanya folder Laravel `public/`, dengan source, `.env`, vendor, storage privat dan artefak QA di luar web root. Jangan upload seluruh workspace ke document root. Paket yang tidak mendukung pemisahan ini belum layak dipilih. |
| **BLOCKER B5 — database target** | Sembilan migration terkini harus direhearsal pada database demo kosong dengan engine/versi target. Bukti MySQL lama tidak mencakup seluruh perubahan terbaru; MariaDB belum terverifikasi. |
| **BLOCKER B6 — khusus Groq live** | Perlu pekerjaan source terpisah untuk kebijakan runtime demo production/MySQL yang aman, beserta pengujian. Tidak menghalangi demo dengan hasil tersimpan dan live OFF. |
| **REQUIRED** | PHP/extensions sesuai lockfile, vendor produksi, build Vite lengkap, akses menjalankan tugas Artisan secara aman, writable storage/cache, satu database terpisah, backup awal dan pengujian setelah upload. |
| **OPTIONAL** | SSH penuh jika ada alternatif CLI aman, cron jika kelak ada scheduler, Redis/worker jika kelak ada job asynchronous, SMTP jika fitur email ditambahkan, optimasi OPcache dan self-host font. |

## 3. Lingkungan dan spesifikasi hosting

### Hasil pemeriksaan source lokal

- `composer.json`: PHP **`^8.2`**, Laravel **`^12.0`**. `composer.lock`: Laravel **12.70.0**, 77 package runtime dan 35 package development. Tinker masih berada dalam require produksi; `--no-dev` tidak menghapusnya.
- Composer runtime API yang diminta Laravel adalah **`^2.2`**: gunakan Composer 2 yang memenuhi lockfile. Beberapa package Nette pada lock membatasi rentang PHP sampai 8.5; jangan menganggap versi PHP mendatang otomatis kompatibel.
- Vite terkunci **7.3.7**; engine build Node **`^20.19.0 || >=22.12.0`**. Tailwind Vite 4.3.3, Chart.js 4.5.1 dan Bootstrap berasal dari dependency existing.
- `public/build/manifest.json` tersedia; semua file/aset yang dirujuk ditemukan. `public/hot` tidak ada. Build terakhir yang diperiksa dari log lulus; **build tidak dijalankan ulang** dalam audit read-only ini.
- Aset build lokal sekitar 1,05 MB; vendor lokal sekitar 66,9 MB termasuk dependency development. Ini bukan ukuran release produksi final atau hasil uji kapasitas hosting.
- `storage` dan `bootstrap/cache` lokal bermode 0755 dan writable oleh pengguna workspace. Hal ini tidak membuktikan UID/proses PHP hosting dapat menulisnya. Tidak ada cached `config.php` lokal; cache package/service tetap tidak perlu disalin dari mesin development.

### Spesifikasi minimum praktis untuk ditanyakan

| Komponen | Kebutuhan |
|---|---|
| PHP | Minimal 8.2 sesuai source; pilihan 8.3/8.4 dapat dipertimbangkan jika seluruh platform requirements lulus. Versi CLI dan web harus sama-sama cocok. |
| Ekstensi runtime dari lock | ctype, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl, pcre, session, tokenizer; ditambah **PDO dan pdo_mysql** untuk database target. Beberapa sudah built-in, tetap verifikasi. |
| HTTP Groq, jika kelak diaktifkan | Transport HTTPS Guzzle berfungsi, disarankan cURL, CA certificate valid, DNS dan outbound TCP 443 ke endpoint Groq. Tidak diperlukan untuk membaca hasil tersimpan. |
| Database | Satu database MySQL/MariaDB terpisah, InnoDB, FK/unique/index/transaction/row lock dan JSON yang sesuai. MySQL 8.0.16+ adalah batas praktis usulan untuk CHECK yang ditegakkan; MySQL 8.4 paling dekat dengan bukti historis. Versi target tetap harus diuji. |
| Web server | PHP handler dan rewrite Apache/LiteSpeed yang menjalankan `public/.htaccess`, atau konfigurasi setara; document root ke `public/`. |
| TLS | Sertifikat HTTPS, renewal yang tersedia, redirect HTTP→HTTPS dan URL aset HTTPS. |
| CLI | Cara aman menjalankan PHP/Artisan sekali-sekali: SSH, terminal panel, atau bantuan provider. Jangan menggantinya dengan route migrasi publik. |
| Memory/disk | Anggaran awal demo kecil: memory_limit PHP 128 MB sebagai titik awal, 256 MB lebih nyaman; 500 MB ruang aplikasi awal, sekitar 1 GB lebih longgar untuk log/backup. Ini estimasi perencanaan, bukan minimum resmi Laravel atau kapasitas yang telah diuji di hosting. Tanyakan juga batas inode/CPU/proses PHP yang berbeda dari memory_limit. |
| Timeout | Alur akademik biasa tidak membutuhkan proses panjang. Jika live AI kelak disetujui, batas HTTP/PHP harus melebihi request provider 25 detik beserta overhead; target praktis 60 detik perlu dibuktikan. |

Ekstensi bcmath/gd/intl/zip pada image Docker bukan otomatis kebutuhan semua fitur demo. OPcache dianjurkan; zip berguna untuk instalasi/ekstraksi. Tidak perlu membeli VPS hanya untuk mempertahankan daftar ekstensi atau proses development Docker.

### Checklist sebelum membeli

- [ ] Provider menyebut **versi PHP web dan CLI**, daftar ekstensi aktif, serta dapat mengubah pengaturannya.
- [ ] Domain/subdomain dapat diarahkan tepat ke folder `public/`, dengan aplikasi privat di luar root.
- [ ] Rewrite, `.htaccess`, HTTPS dan sertifikat/renewal tersedia pada paket yang benar, bukan hanya pada paket lebih mahal.
- [ ] Ada satu DB beserta versi engine, kuota, connection limit dan hak CREATE/ALTER/INDEX/REFERENCES pada DB tersebut; tidak perlu akses root server.
- [ ] Ada jalur CLI/dukungan menjalankan Artisan dan pengecekan platform; Composer di hosting tidak wajib bila vendor produksi dibangun di lingkungan kompatibel.
- [ ] Upload SFTP/panel, akses log privat, permission storage/cache, backup dan restore tersedia.
- [ ] Tanyakan CPU, inode, jumlah worker PHP, memory_limit dan timeout nyata. Label “unlimited” bukan bukti kapasitas.
- [ ] Jika live AI menjadi syarat: tanyakan outbound HTTPS, CA, DNS, cURL/transport dan timeout, **setelah** blocker source live direncanakan.
- [ ] Cek biaya domain, SSL, backup, batas database dan biaya perpanjangan. Audit ini tidak memverifikasi harga atau merekomendasikan merek hosting tertentu.

## 4. Composer, aset, cron, queue dan ketergantungan lokal

Release perlu vendor dari `composer.lock` dengan dependency production, bukan sekadar source Git. Jangan memakai `composer update` atau `--ignore-platform-reqs` sebagai jalan pintas. **Jangan menjalankan `composer setup`**: script itu mencakup key generation, migration dan npm install. `composer test` juga membersihkan config; tidak dipakai dalam audit ini.

Lifecycle `post-autoload-dump` melakukan package discovery dan dapat menulis bootstrap cache. Kelak instalasi dependency harus dilakukan pada folder release terpisah, dengan PHP/extensions yang cocok dan script lifecycle direview. Verifikasi platform menggunakan `composer check-platform-reqs --no-dev` pada runtime target bila fasilitasnya tersedia.

Build Vite dapat dikerjakan sebelum upload. Hosting **tidak perlu Node/npm berjalan**, Vite dev server, `artisan serve`, Pail atau `composer dev`. `public/build` ignored di Git: mengambil repo saja tidak menyertakan build. Upload manifest dan seluruh aset build yang berpasangan, tanpa `public/hot`. CSS/JS utama dibundel lokal; Google Fonts masih eksternal dan memiliki fallback. Ketersediaan glyph font asli pada hosting belum diperiksa.

Tidak ditemukan job aplikasi yang di-dispatch/ShouldQueue maupun academic scheduled task. `routes/console.php` hanya memuat command inspire. Untuk demo saat ini:

- `QUEUE_CONNECTION=sync` cukup; tidak perlu worker/daemon/Supervisor.
- Cron scheduler tidak diperlukan untuk alur existing. Tambahkan hanya jika nanti memang ada scheduled task; backup panel dapat memiliki jadwal sendiri.
- File session/cache dapat dipakai pada satu hosting dengan filesystem persisten dan permission benar. Database driver juga tersedia, tetapi memerlukan tabel dan validasi akses.
- Jangan memakai cache/session array testing untuk hosting. File locks dan rate limiter harus bekerja pada filesystem yang sama untuk semua proses aplikasi.

`scripts/wsl-*.php`, `wsl-dev.py`, manifest runtime, path `/home/...`, Docker bridge dan browser QA adalah alat lokal, bukan deployment runner. Sebagian helper mengimpor `Tests`/factory; produksi `--no-dev` tidak menyediakan Faker/namespace test. Jangan mengandalkan script tersebut untuk mengisi database hosting. Jangan menghapus kelas keamanan bernama WSL dari `app/Services` secara sembarangan; guard-nya justru membatasi fitur live.

## 5. Database dan migration

Ada **9 file migration**, membentuk **24 tabel**: users/session/password reset, cache/lock, jobs/batches/failed jobs, serta domain program studi, dosen, mahasiswa, semester, mata kuliah, kelas, KRS/detail/riwayat pengajuan, nilai, kehadiran, analisis/faktor/rekomendasi/tindak lanjut.

### Risiko kompatibilitas yang perlu direhearsal

| Area source | Implikasi target |
|---|---|
| FK dan composite unique | Gunakan InnoDB; urutan insert parent/child dan tipe foreignId harus konsisten. Jangan mematikan FK untuk memaksa impor lolos. |
| KRS, keputusan, finalisasi | Layanan memakai transaksi dan `lockForUpdate`. SQLite tidak membuktikan locking MySQL; perlu uji duplikasi/retry, kapasitas dan finalisasi di target. |
| Migration level akun | CHECK ditambahkan hanya ketika driver bernama mysql. Native driver mariadb melewati cabang ini; koneksi mysql ke MariaDB dapat menemui perbedaan DDL. Sintaks `DROP CHECK` pada down perlu diuji khusus MariaDB, bukan dianggap identik. |
| JSON analisis/snapshot | Query JSON path, tipe JSON, indeks dan agregasi harus diuji pada engine/version yang benar. Jangan memindahkan DDL SQLite mentah ke MySQL. |
| Migration 8 November | Skor/kategori analisis menjadi nullable melalui `change()`. Down dapat gagal bila data null sudah ada; tidak layak dijadikan rollback spontan. |
| Migration 9 November | Normalisasi username memakai transaksi dan preflight collision/length; tidak membuat username yang hilang. Collation/uniqueness harus benar, terutama pada data impor. |
| DDL MySQL | Tidak seluruh perubahan schema dapat dibatalkan sebagai satu transaksi; kegagalan sebagian perlu penanganan pada database demo terpisah. |
| Waktu | App timezone UTC; periode KRS merupakan wall time Asia/Jakarta, sedangkan timestamp peristiwa ditampilkan WIB. Jangan mengubah semua timezone ke WIB tanpa mengecek semantik ini. |

`config/database.php` menyediakan mysql dan mariadb, strict mode, utf8mb4/utf8mb4_unicode_ci. Dukungan konfigurasi bukan sertifikat kompatibilitas MariaDB. MySQL sangat lama tidak cocok sebagai pilihan hemat biaya.

### Strategi data demo tanpa membawa data privat

**Disarankan: database hosting baru dan dataset sintetis kecil yang dapat dibuat ulang**, bukan dump SQLite workspace atau MySQL operasional.

1. Definisikan dataset: empat akun terpisah, satu mahasiswa dan satu dosen utama, beberapa mata kuliah/kelas, semester historis bernilai final, dan semester aktif untuk demonstrasi KRS.
2. Siapkan initializer/demo seeder khusus yang direview pada pekerjaan terpisah, tidak bergantung manifest WSL/TestCase/Faker runtime. Harus menolak database non-demo/berisi data tidak dikenal, tidak menimpa akun/riwayat, dan menerima password aman melalui mekanisme privat.
3. Isi parent→child sesuai FK, kaitkan mahasiswa/dosen dengan user yang benar, dan biarkan IPS/IPK dihitung layanan existing dari nilai final. Jangan memasukkan angka chart/IPS/IPK statis sebagai pengganti catatan sumber.
4. Sesuaikan periode KRS untuk tanggal demo secara sengaja dalam fixture baru. Satu semester aktif, beban SKS/kapasitas, penugasan dosen dan finalisasi harus saling berkaitan.
5. Siapkan hasil analisis tersimpan yang jelas berlabel simulasi/fixture bila diperlukan. Jangan mengganti metadata mock menjadi live atau mengklaim skor resmi yang tidak tersedia.
6. Ekspor/impor, jika dibutuhkan, hanya dari dataset demo baru yang telah direview, memakai format MySQL yang sesuai. Jangan membawa users/session/password-reset/cache/jobs/log/runtime manifest dari instalasi lokal.

`DatabaseSeeder` hanya berjalan pada local/testing; pada production ia return tanpa mengisi akun. `AcademicDemoSeeder` langsung menolak production. Selain itu, marker `DEMO...`, `...-DEMO` dan domain demo khusus sengaja dikecualikan oleh query operasional. Karena itu, menjalankan seeder lama di lingkungan build lalu mengimpor seluruh hasilnya belum tentu menghasilkan dashboard demo utama yang berisi data.

Admin UI mengelola profil akademik dan pemilihan akun existing; bukan fitur provisioning user/password. Empat akun login harus sudah disiapkan terpisah. Tidak ada register/reset-password mandiri yang lengkap pada route saat ini; jangan bergantung pada SMTP atau tabel password_reset_tokens untuk fitur yang belum dibuat.

## 6. Keamanan demo publik

### Yang sudah ada dalam source

- Login username/password dengan password hashing model, normalisasi username, session regeneration; logout POST menginvalidasi session dan merotasi CSRF token.
- Middleware auth dan level untuk empat area. Owner hanya GET/HEAD pada monitoring, tidak memperoleh hak mutasi melalui UI maupun endpoint area Admin/Dosen.
- Middleware web Laravel mempertahankan CSRF; login diberi throttle 6 per menit, serta throttle pada sejumlah aksi KRS/nilai/AI. Batas berbasis IP bisa memengaruhi banyak penguji pada satu jaringan; uji penggunaan nyata tanpa mematikan perlindungan.
- Validasi server, ownership dan proteksi finalisasi/riwayat tetap ada. Ini tidak membatasi pemilik credential Admin bersama dari sengaja mengubah data demo; pembagian akses tetap penting.
- Disk local memakai storage privat dengan signed URL; source framework dan tes existing menolak pembacaan/penulisan tanpa capability. Jangan mengandalkan ini sebagai pengganti document root yang benar.

### Persiapan wajib

- Environment hosting baru: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS domain sebenarnya, DB khusus demo, `GROQ_LIVE_ENABLED=false` untuk fase awal. Tidak menyalin key/database credential lokal.
- Buat APP_KEY baru **hanya untuk instalasi demo baru yang belum berisi data terenkripsi**; setelah dipakai, simpan dan pertahankan untuk update. Jangan memakai key PHPUnit yang publik atau merotasi key sembarangan pada instalasi existing.
- Cookie: Secure aktif, HttpOnly tetap true, SameSite Lax sesuai kebutuhan, domain/path tepat. Periksa HTTPS/proxy forwarding agar tidak terjadi mixed content, redirect loop atau cookie gagal.
- Password kuat dan berbeda per role; akses Admin/Dosen diberikan terbatas kepada penilai. Untuk sesi sekolah, proteksi password tingkat hosting/subdomain tertutup dapat mengurangi risiko dan biaya penyalahgunaan.
- Simpan `.env`, API key, backup dan log di luar web root; hanya storage dan bootstrap/cache yang perlu write oleh PHP. Jangan chmod 777 atau menjadikan semua source writable.
- Bersihkan session, compiled views, cache konfigurasi development, log dan artefak test dari paket. Regenerasi cache target setelah konfigurasi target siap; jangan membawa path/cache absolut mesin lokal.
- Verifikasi akses HTTP ke `.env`, `.git`, private storage, database/dump, Composer files dan script QA menghasilkan 4xx, bukan isi file.

Pemeriksaan pola secret pada file tracked tidak menemukan signature API key/private key yang dicari; pencocokan key Groq aktual pada build publik dan log lokal menghasilkan **0 kecocokan**, tanpa mencetak nilainya. Ini bukan pemindaian menyeluruh histori Git atau seluruh jenis credential. Key lokal memang ada, tetapi nilainya tidak ditampilkan. Source default tidak memaksa HTTPS; itu tetap tugas konfigurasi hosting.

### Allowlist paket release

Sertakan source aplikasi yang dibutuhkan, config/routes/resources/lang/bootstrap, artisan, migration, Composer lock/vendor produksi, public assets/build serta direktori runtime kosong yang benar. Jangan menghapus kelas app karena namanya terlihat lokal tanpa meninjau referensinya.

Kecualikan `.git`, `.agents`, `.codex`, editor/browser profiles, `.auth`, `.env*` aktif, credential/manifest WSL, `.aws`, QA artifacts/screenshots, backup/dump/SQLite, logs/sessions/cache lokal, node_modules, Docker/WSL runner, test harness dan file arsip. **Ignore Git tidak otomatis mengecualikan file saat zip seluruh folder.** Saat audit terdapat ribuan artefak QA lokal, sehingga pengemasan allowlist penting.

Tidak ditemukan route installer/migrate/seed/QA publik pada `routes/web.php`; route `/area/*` masih dilindungi auth/level dan hanya placeholder. `/up` adalah health endpoint. Script development tidak boleh ditambahkan sebagai endpoint web atau diunggah ke public. `public/storage` belum ada dan tidak tampak perlu bagi alur demo saat ini; jika kelak dibuat symlink, hanya ke `storage/app/public`, tidak ke private/storage seluruhnya.

Security headers, advisori dependency terbaru, kebijakan backup provider dan konfigurasi server nyata belum diverifikasi. Header dasar dapat ditambahkan melalui hosting setelah diuji; jangan memasang CSP/HSTS yang merusak aset atau HTTPS tanpa rehearsal.

## 7. Groq AI dan fallback demo

| Aspek | Temuan |
|---|---|
| Secret/model | Key dibaca server-side melalui config services; model existing `openai/gpt-oss-20b`. Tidak ada kebutuhan key di frontend/VITE variable. |
| Runtime guard | `WslDevelopmentIdentity::verify()` mensyaratkan local + SQLite + path/database manifest cocok. Service memeriksa guard **sebelum** fingerprint lookup. Production/MySQL tidak dapat menjalankan POST generate maupun reuse tersebut dengan source sekarang. |
| UI | Tombol analisis baru hanya enabled pada local/testing dengan flag, key, URL dan model sesuai. Production tetap membaca riwayat. |
| Koneksi | Endpoint HTTPS Groq dibatasi tepat, redirect provider tidak diikuti; connect timeout 5 detik, total 25 detik, tanpa retry otomatis. |
| Batas request | Route 6 POST/menit, service dua percobaan baru per menit per akun, lock 90 detik/wait 1 detik. Belum ada jaminan batas biaya global seluruh akun untuk demo publik. |
| Data terkirim | Agregat akademik, termasuk IPS/IPK/SKS dari catatan; tidak mengirim nama, NIM, email, nomor telepon atau teks bebas identitas. |
| Validasi/error | Respons JSON dibatasi struktur/ukuran/panjang, error provider dipetakan ke pesan pengguna; hasil invalid tidak disimpan. Riwayat lama tidak dihapus. |
| Reuse | Fingerprint memakai indikator, model dan metode `descriptive-v2`; bekerja hanya setelah runtime guard lulus, bukan jalan mem-bypass guard production. |
| Jika layanan gagal | GET riwayat/monitoring tetap berfungsi; tidak ada auto request ketika login/reload. Error tidak otomatis diganti analisis palsu atau mock yang diklaim live. |

**Fase demo termurah:** simpan key di luar paket atau kosongkan pada target baru, flag live OFF, tampilkan hasil tersimpan/simulasi dengan provenance yang jujur. Hosting tidak perlu memenuhi outbound Groq untuk fase ini.

Jika live wajib pada penilaian, buat tugas implementasi terpisah: runtime allowlist database demo production yang aman, consent, akun yang diizinkan, batas biaya/permintaan, locking serta fixture identity yang tidak bergantung WSL. Jangan menghapus guard/validasi/rate limit; jangan memakai testing/local di internet. Uji dengan HTTP fake terlebih dahulu, baru pertimbangkan satu live smoke test melalui persetujuan terpisah. Audit ini tidak melakukan request live atau mengecek kuota/ketersediaan model di provider.

## 8. Bukti fitur demo yang sudah ada

| Alur | Bukti lokal existing | Batas kesiapan hosting |
|---|---|---|
| Login/logout empat role | Tes auth/demo account dan UI lintas role pada laporan QA | Akun/password hosting belum disiapkan; HTTPS/session target belum diuji |
| CRUD Admin | Tes CRUD, validasi, FK protection, authorization; laporan targeted Admin | Browser mutasi aman memakai fixture/intersepsi; bukan bukti mutasi pada hosting |
| KRS dan approval | Tes submit, versi/snapshot, tolak/ajukan ulang/setujui, batas akses | Engine locking, periode KRS dan data awal hosting harus benar |
| Kehadiran/nilai Dosen | Tes penugasan dosen, peserta resmi, save/finalize | Uji driver MySQL target dan transaksi finalisasi |
| IPS/IPK | Tes hanya nilai final valid, semester dan percobaan mata kuliah | Jangan menyalin angka chart sebagai pengganti data sumber |
| Monitoring Owner | QA terbaru 11 halaman, filter/detail/keyboard/read-only dan grafik | Tidak membuktikan performa atau konfigurasi shared hosting |
| AI tersimpan | Tes provenance/privacy, riwayat/rekomendasi dan mocked Groq | Dataset tersimpan hosting perlu disiapkan; live production masih diblokir |

Artefak yang dibaca, bukan tes baru pada audit ini:

- `qa-artifacts/owner-polishing/phpunit.log`: **15 tests/459 assertions PASS**, 9 November; laporan [polishing Owner](final-owner-monitoring-ui-polishing.md).
- `qa-artifacts/global-detail-dialog/resume/phpunit.log`: **69 tests/1.404 assertions PASS**, cakupan SQLite terarah; [audit global](final-global-detail-form-dialog-ui-audit.md).
- [Redesign Admin](final-admin-akademik-ui-redesign.md): skenario CRUD/filter/authorization dan fixture aman; [redesign Owner](final-owner-monitoring-ui-redesign.md): agregasi/chart dan saved Groq mocked.
- `qa-artifacts/final/mysql-tests.log`: **57 tests/1.003 assertions PASS**, artefak 7 November. Dokumentasi MySQL lokal historis menyebut **8.4.11**. Bukti ini mendahului migration 8/9 November dan **tidak mensertifikasi checkpoint `fca168c` atau MariaDB**.
- Log build polishing: Vite PASS 5,16 detik; manifest/asetnya diperiksa keberadaannya saat audit ini. Tidak ada rebuild atau test suite mutatif baru.

## 9. Rencana deployment — belum dijalankan

Semua langkah ini untuk pekerjaan deployment yang disetujui kemudian, bukan perintah yang dijalankan oleh audit.

1. Tetapkan cakupan presentasi: alur akademik + AI tersimpan atau live wajib. Jika live wajib, selesaikan B6 dahulu.
2. Pilih paket berdasarkan checklist kemampuan; catat versi PHP web/CLI, database, document root, limit dan akses tugas Artisan.
3. Siapkan initializer/data demo hosting yang direview; buat empat password baru secara privat dan rencanakan akses terbatas bagi penguji.
4. Rehearsal checkpoint ini beserta sembilan migration pada database **demo kosong terpisah** dengan engine/version target. Periksa collision username, JSON, FK, finalisasi dan KRS; jangan menyentuh MySQL operasional atau SQLite utama.
5. Buat release allowlist terpisah. Persiapkan vendor locked production dan build Vite di lingkungan yang kompatibel; review Composer scripts. Dependency download jika diperlukan merupakan pekerjaan berikutnya yang memerlukan izin, bukan dilakukan audit ini.
6. Buat satu DB hosting khusus dengan user khusus dan hak secukupnya. Verifikasi nama database/server sebelum migrasi/import apa pun; jangan menganggap DB default panel adalah target yang benar.
7. Provision environment/APP_KEY khusus instalasi demo baru dan HTTPS, session/cache/queue yang dipilih. Jangan copy `.env` lokal atau menaruh key di build frontend.
8. Upload release, set root `public/`, atur ownership/writable dirs. Jalankan pemeriksaan platform dan tugas Artisan melalui jalur CLI aman; jangan memakai public installer.
9. Setelah target DB kosong terverifikasi dan izin deployment tersedia, jalankan migration dan initializer demo yang direview. Tidak memakai migrate:fresh/reset sebagai prosedur rutin atau pada DB utama. Periksa migration status dan jumlah/relasi data.
10. Regenerasi cache konfigurasi/view target bila digunakan; jangan membawa bootstrap cache/path lama. Pastikan mode debug OFF dan asset URL benar setelah cache dibentuk.
11. Buat backup privat database demo awal dan snapshot release, uji pemulihan pada salinan yang aman, lalu jalankan checklist pasca-deploy.
12. Buka akses hanya setelah semua gerbang wajib lulus. Jika gagal, tutup akses dan pulihkan release/database demo yang sesuai; jangan menganggap rollback source otomatis membatalkan perubahan data akademik.

## 10. Checklist setelah deployment

- [ ] `/`, `/login`, `/up` dan aset build berhasil melalui HTTPS; tidak ada referensi localhost/hot-server, mixed content atau error browser.
- [ ] PHP platform requirements lulus pada target; extension/config CLI dan web sama-sama benar.
- [ ] `.env`, `.git`, private files, SQL/SQLite, log, backup, vendor/source dan script lokal tidak dapat diunduh lewat HTTP.
- [ ] Cookie Secure/HttpOnly/SameSite/domain benar; login/logout empat role bekerja; route langsung lintas role ditolak; CSRF gagal tanpa token dan login throttle tetap berlaku.
- [ ] Empat akun memakai password baru, hanya data sintetis yang ditampilkan, tidak ada credential/key pada HTML/JS/error response.
- [ ] Admin menambah/mengubah/menghapus satu record QA yang tidak berelasi; penghapusan data berelasi ditolak. Jangan menguji hapus dengan record utama demo.
- [ ] Mahasiswa memilih kelas pada window KRS yang benar, mengajukan, ditolak/direvisi/disetujui; versi historis tetap utuh dan kapasitas/batas SKS berlaku.
- [ ] Dosen hanya melihat/mengubah kelas tugasnya; kehadiran dan nilai draft bekerja; finalisasi tidak dapat diulang/diubah sembarangan.
- [ ] IPS/IPK dan chart cocok dengan data final; nol berbeda dari belum tersedia; semester filter/pagination/reset tetap benar.
- [ ] Owner tetap read-only, seluruh halaman/detail/report berfungsi; uji mobile/tablet dan scroll lokal.
- [ ] Riwayat analisis/rekomendasi terbaca tanpa request Groq; live OFF tetap tidak memanggil provider. Bila kelak live disetujui, uji timeout/429/error dan quota secara terpisah.
- [ ] Storage/cache writable, file privat tetap privat, log tidak bocor secrets; backup dapat dipulihkan dan akses hosting/demo dapat ditutup sesudah penilaian.

## 11. Kompleksitas dan hal yang belum terverifikasi

**Kompleksitas sedang** untuk demo akademik + hasil AI tersimpan: pekerjaan terbesar adalah paket data/akun non-WSL dan rehearsal database, bukan memindahkan CSS atau menyediakan Docker. Perkiraan kerja teknis setelah akses/hosting tersedia sekitar **1–2 hari**, bukan SLA; dukungan provider, kompatibilitas MariaDB dan perbaikan migration dapat memperpanjangnya.

**Kompleksitas sedang–tinggi untuk live AI production**, karena perlu desain guard/provisioning/biaya dan regresi tambahan; belum layak diberi estimasi pasti sebelum scoped implementation. Jangan membeli paket lebih mahal hanya karena mengira worker/Node server akan menyelesaikan batas source ini.

Belum terverifikasi: provider/harga/perpanjangan, PHP/extensions/permission nyata, seluruh migration checkpoint terbaru pada target MySQL/MariaDB, fresh hosting demo initializer, database restore, konkurensi/kapasitas shared hosting, mail delivery, TLS/proxy/security headers, advisori dependency terbaru, serta konektivitas/kuota/model Groq di hosting. Bukti localhost tidak menutup item-item ini.

**Batas audit:** hanya pemeriksaan lokal read-only dan pembuatan laporan ini. Tidak menjalankan boot aplikasi/Artisan, build, install/update dependency, migrate/seed/reset, koneksi MySQL operasional, live Groq, perubahan `.env`/APP_KEY/Docker/MCP, operasi Git mutatif, atau deployment. Nilai rahasia tidak ditampilkan. Working tree awal hanya memiliki `.agents/` untracked; source checkpoint dipertahankan.
