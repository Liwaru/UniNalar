# UniNalar — instalasi shared hosting untuk demo sekolah

Dokumen ini adalah prosedur **nanti pada hosting yang telah dipilih dan disetujui**, bukan catatan deployment yang sudah dilakukan. Jangan jalankan contoh di workspace WSL/database utama. Hosting, domain, database target, DNS dan Groq live: **PENDING HOSTING**.

## 1. Syarat sebelum memilih paket

- PHP minimal 8.2 sesuai composer.lock; PHP web dan CLI harus kompatibel. Verifikasi extension ctype, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl, pcre, session, tokenizer serta PDO/pdo_mysql; untuk Groq pilih ext-curl aktif dan CA TLS valid.
- Satu database demo khusus, InnoDB, strict mode, FK/unique/JSON/row lock. MySQL **8.4.11** telah diuji; MariaDB/versi lain masih memerlukan rehearsal sebelum dipakai.
- HTTPS, rewrite, document root langsung ke `public/`, aplikasi/config/vendor/storage privat di luar root publik. Jangan menempatkan root proyek di public_html hanya dengan mengandalkan larangan `.env` dari .htaccess.
- Ada cara aman menjalankan PHP CLI/Artisan: SSH, Terminal panel, cron CLI sementara atau bantuan provider. Composer tidak wajib tersedia di hosting bila vendor production telah disiapkan lokal. Jika tidak ada cara CLI aman sama sekali, **NO-GO** untuk prosedur ini; jangan membuat installer/migrate route publik.
- Memory PHP 128 MB sebagai titik awal demo, 256 MB lebih nyaman; anggaran storage 1 GB lebih longgar. Ini perencanaan, bukan batas resmi hasil benchmark hosting. Tanyakan CPU, inode, entry process, memory dan hard timeout yang sebenarnya.
- Untuk live nanti: DNS/outbound HTTPS TCP 443 menuju api.groq.com, tanpa menonaktifkan verifikasi TLS; timeout PHP/proxy harus lebih besar dari HTTP 25 detik + query/write. Minta konfirmasi 45–60 detik sebagai anggaran awal, lalu ukur.
- Aplikasi sinkron ini tidak membutuhkan Node server, Docker, Redis, Supervisor, queue worker permanen atau scheduler/cron rutin. Cron hanya mungkin menjadi jalur CLI sementara bila tanpa SSH.

## 2. Paket release lokal

Paket memakai allowlist source, vendor **77 package runtime tanpa dev**, build Vite dan template `.env.production.example`. Tidak membawa `.env`, database, seeders development, tests, WSL scripts, `.agents`, `.git`, browser profile, log atau artefak QA.

Pembuat staging: `scripts/prepare-shared-hosting-release.py`. Gunakan direktori baru; tidak overwrite:

```sh
python3 scripts/prepare-shared-hosting-release.py qa-artifacts/releases/release-baru
```

Di **salinan staging itu**, gunakan Composer existing dengan `COMPOSER_DISABLE_NETWORK=1`, `install --no-dev --no-scripts --no-plugins --no-interaction --prefer-dist --optimize-autoloader`. Ini boleh hanya bila seluruh dependency lock sudah tersedia lokal; jika meminta download, hentikan dan minta izin. Jangan menjalankannya pada vendor development. Jangan `composer update`, `composer run setup`, atau `npm install` sebagai shortcut deployment.

Lakukan platform check, package discovery, cache verification dan smoke pada salinan terisolasi. Bersihkan kembali cache/config/route/view/session/log staging sebelum arsip. Setelah itu:

```sh
python3 scripts/prepare-shared-hosting-release.py qa-artifacts/releases/release-baru --archive
```

`release-source.json` menandai HEAD sumber dan bahwa working tree disertakan. Arsip final memiliki checksum SHA-256. Untuk source baru setelah tahap ini, bangun ulang release; jangan memakai arsip lama yang tidak menyertakan perubahan tersebut. Jangan mengunggah folder qa-artifacts atau seluruh workspace sebagai paket.

## 3. Pilihan A — SSH/Terminal, Composer dan Artisan tersedia

1. Konfirmasi alamat/domain, instalasi demo baru, versi PHP, DB host/name/user dan persetujuan deployment. Gunakan DB terpisah, bukan database website existing.
2. Upload/extract release ke direktori privat, misalnya `/home/AKUN/uninalar/releases/RELEASE`. Jangan menimpa website lain. Arahkan subdomain/document root ke direktori release `public/`, atau gunakan symlink current yang disetujui provider. Jangan mengubah index.php agar Laravel root menjadi publik.
3. Jika memakai arsip ber-vendor, tidak perlu Composer install di server. Jalankan `composer check-platform-reqs --no-dev` jika Composer tersedia. Jika memasang dependency di server kelak, perlu persetujuan download terlebih dahulu dan gunakan lockfile/no-dev, bukan update.
4. Buat `.env` baru dari `.env.production.example` di root aplikasi privat. Isi placeholder secara privat melalui panel/SSH, bukan chat/repository. Jangan menyalin `.env` WSL. Pilih APP_ENV production, debug false, APP_URL HTTPS, DB MySQL target, session/cache file dan queue sync. APP timezone source tetap UTC; window KRS memakai WIB pada service.
5. Pada instalasi baru sebelum data dibuat, hasilkan APP_KEY deployment khusus sekali melalui `php artisan key:generate --force`. Jangan mengganti key instalasi existing/WSL. Simpan key untuk restore selanjutnya. Atur permission `.env` sesuai UID PHP (0600 jika pemilik proses sama).
6. Pastikan storage dan bootstrap/cache writable oleh akun PHP; hindari 0777. Jangan menyalin file cache/config/session dari lokal. Jalankan `php artisan package:discover`, `php artisan config:clear`, dan periksa konfigurasi target secara privat tanpa mencetak secret.
7. Verifikasi koneksi aktual hanya menuju DB demo yang disetujui, termasuk nama DB/host/user dan kondisi kosong. Jalankan `php artisan migrate --force`, lalu `php artisan migrate:status`. Jangan migrate:fresh/reset/seed development.
8. Ikuti initializer di bagian 5. Setelah selesai, disable provisioning dan simpan credentials secara privat.
9. Jalankan `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. Jangan cache sebelum placeholder environment diganti. Upload build Vite existing; tidak membutuhkan Vite dev server/public/hot.
10. Storage link tidak diperlukan untuk logo/build static existing. Hanya jika fitur file publik benar-benar memerlukan, gunakan `php artisan storage:link` menuju `storage/app/public`, bukan seluruh storage/private. Jangan expose file log atau credentials.
11. Lakukan checklist online pada dokumen terpisah. Groq tetap OFF sampai checkpoint aktivasi disetujui.

## 4. Pilihan B — tanpa SSH atau Composer di hosting

Gunakan arsip ber-vendor production dan build Vite terverifikasi. Upload/extract melalui panel/SFTP yang disetujui. Struktur privat dan document root harus sama amannya dengan pilihan A.

Artisan tetap memerlukan PHP CLI. Pilih salah satu:

- Terminal pada panel walau login SSH tidak tersedia;
- bantuan provider untuk menjalankan daftar perintah yang direview;
- satu cron CLI sementara pada path privat, kemudian hapus jadwal segera setelah berhasil. Jangan membuat cron periodik untuk initializer; rerun menolak overwrite dan file credentials existing. Log cron harus ke direktori privat, tanpa mengekspor secret ke command line.

Contoh pola cron hanya setelah path/versi PHP hosting diverifikasi: `/PATH/PHP /home/AKUN/uninalar/current/artisan migrate --force`. Eksekusi initializer membutuhkan opsi nama DB dan path credentials privat; jangan memasukkan password/key dalam argumen. Setelah each command, periksa exit/status secara privat lalu hapus jadwal sebelum melanjutkan.

Jangan memakai web route/debug.php/public shell untuk migration, seeding, Composer atau cache clear. phpMyAdmin dapat memeriksa DB/backup, tetapi bukan pengganti prosedur Laravel ini dengan menyalin dump SQLite development. Bila provider tidak menyediakan salah satu jalur CLI aman atau root publik terpisah, pilih paket lain.

## 5. Database sintetis dan empat akun

Initializer baru `uninalar:demo-prepare` bukan DatabaseSeeder development. Hanya menerima:

- environment production (test SQLite memory hanya untuk QA), debug false dan URL HTTPS;
- opt-in provisioning eksplisit, mode persis `production-demo`;
- nama DB yang cocok konfigurasi, konfirmasi CLI dan `SELECT DATABASE()` aktual;
- MySQL/MariaDB target tanpa DB_URL override, schema migration lengkap;
- seluruh tabel domain/users kosong, budget global belum terpakai;
- Groq live dan Groq demo keduanya **false**.

Di **hosting baru yang disetujui**, sementara set konfigurasi:

```dotenv
UNINALAR_DEMO_PROVISIONING_ENABLED=true
UNINALAR_DEMO_PROVISIONING_MODE=production-demo
UNINALAR_DEMO_DATABASE=NAMA_DB_DEMO_AKTUAL
GROQ_LIVE_ENABLED=false
GROQ_DEMO_ENABLED=false
```

Bersihkan config cache setelah perubahan. Buat direktori privat **di luar aplikasi dan document root**, permission 0700. Jalankan nanti:

```sh
php artisan uninalar:demo-prepare \
  --confirm-database=NAMA_DB_DEMO_AKTUAL \
  --credentials-file=/home/AKUN/private/uninalar-demo-accounts.json
```

Perintah menghasilkan empat password berbeda masing-masing 32 karakter acak, menyimpan hash ke DB, dan menulis plaintext hanya ke file baru 0600 di direktori privat. Password tidak dicetak, tidak dijadikan argumen dan tidak ditanam di source. File existing tidak ditimpa. User ID mahasiswa dicetak untuk allowlist Groq nanti.

Akun: `demo_mahasiswa`, `demo_dosen`, `demo_admin`, `demo_pimpinan`. Jangan membagikan seluruh credentials kepada pengunjung; sampaikan hanya akun yang diperlukan secara privat kepada guru. Tidak ada registrasi publik atau UI reset password baru.

Isi dataset: 1 program, 1 dosen, 1 mahasiswa, 2 semester, 6 mata kuliah/kelas, 2 KRS, 6 pilihan kelas, 3 nilai historis final, 12 catatan kehadiran, 1 history persetujuan, 1 analisis fixture mock dan 1 rekomendasi fixture. Data diberi nama/kode simulasi; source `hosting_demo_fixture`/transport mock, tidak pernah mengaku berasal dari Groq. IPK/IPS historis awal 3,50. Marker tidak menggunakan pola development yang sengaja dikecualikan dashboard.

KRS semester aktif dimulai **draft**, dengan tiga pilihan. Jendela KRS dibuat relatif saat provisioning, berakhir 14 hari kemudian. Guru dapat mengikuti:

1. Mahasiswa memeriksa pilihan lalu mengajukan KRS.
2. Admin menyetujui pengajuan.
3. Dosen memasukkan dan memfinalisasi nilai mahasiswa resmi.
4. Mahasiswa memeriksa IPS/IPK, lalu meminta AI bila live sudah disetujui.
5. Pimpinan membaca monitoring tanpa hak tulis.

Sebelum persetujuan KRS aktif, analisis semester aktif akan ditolak karena belum ada mata kuliah disetujui. Ini aturan akademik existing, bukan error readiness provider. Semester historis sudah disetujui dan memiliki nilai/kehadiran untuk monitoring.

Setelah berhasil: matikan `UNINALAR_DEMO_PROVISIONING_ENABLED`, kosongkan mode provisioning bila tidak diperlukan, bangun ulang config cache. Simpan credentials dalam vault/penyimpanan privat; jangan di public_html, backup publik, screenshot atau Git. Jangan mencoba initializer ulang untuk mereset data. Ia akan menolak DB berisi data; tidak menghapus atau menimpa akun/history/budget.

Jika gagal atau koneksi putus: tidak ada retry otomatis. File credentials yang sudah dibuat tetap dipertahankan untuk recovery jika commit DB belum pasti. Periksa DB demo dan log aman secara privat; jangan menghapus data/credentials untuk memaksa rerun.

### Pergantian password

Nonaktifkan live sementara, batasi akses evaluasi, aktifkan kembali opt-in provisioning hanya pada DB demo yang benar dan kosongkan config cache. Gunakan CLI tersembunyi:

```sh
php artisan uninalar:demo-password demo_mahasiswa --confirm-database=NAMA_DB_DEMO_AKTUAL
```

Perintah hanya menerima empat akun fixture yang cocok username/email/role; meminta password acak baru minimal 24 karakter dan konfirmasi melalui hidden input. Nilai tidak menjadi argumen/log. Remember token dibatalkan. Untuk mengakhiri sesi existing, ubah SESSION_COOKIE hosting ke nama versi baru lalu cache ulang; perubahan password saja tidak menjamin file session existing terputus. Perbarui vault privat, matikan provisioning lagi. Jangan menerapkan langkah ini pada WSL atau akun non-demo.

## 6. Konfigurasi Groq — tetap OFF

Key hanya secret server; jangan kirim melalui chat, `VITE_*`, JS atau repository. Nilai yang diterima source saat nanti diaktifkan:

| Variabel | Syarat |
| --- | --- |
| GROQ_API_KEY | Key privat server, bukan contoh di source |
| GROQ_MODEL | `openai/gpt-oss-20b` |
| GROQ_BASE_URL | `https://api.groq.com/openai/v1` persis |
| GROQ_LIVE_ENABLED | Default false; true hanya sesudah persetujuan live |
| GROQ_DEMO_ENABLED | Default false; true hanya sesudah verifikasi demo |
| GROQ_DEMO_MODE | `production-demo` persis |
| GROQ_DEMO_ENVIRONMENTS | `production` untuk instalasi staging/demo terpisah |
| GROQ_DEMO_DATABASE | Nama DB demo aktual, cocok koneksi |
| GROQ_DEMO_USER_IDS | CSV **ID user mahasiswa**, bukan ID profil; ambil output initializer |
| GROQ_DEMO_GLOBAL_LIMIT | Integer 1–10.000, tetapkan konservatif sesuai persetujuan |
| GROQ_DEMO_USER_LIMIT | Integer 1–10.000; bukan reset harian |

Default template: enabled false, mode/allowlist kosong, limits 0. Provisioning harus OFF sebelum live. Session/cache harus persisten (file/database paling sederhana), Secure/HttpOnly true, debug false, APP_URL HTTPS dan DB_URL kosong. Guard WSL local tetap utuh; jangan memakai APP_ENV local/testing untuk bypass.

Kuota menghitung reservasi konservatif seumur tabel, termasuk timeout/gagal simpan. Tidak direfund otomatis atau direset cache. Global lease 120 detik, HTTP 25 detik, rate/window user dan lock tetap. Hasil matching live dipakai ulang; mock tidak menggantikan live. Riwayat GET tersedia tanpa provider.

**PENDING HOSTING:** pastikan TLS/outbound, budget, akun allowlist, timeout dan key privat; minta persetujuan eksplisit satu attempt live. Baru sesudahnya aktivasi, config cache, klik dengan consent, verifikasi respons/DB/provenance/usage tanpa raw provider/key log. Tidak ada health/model probe atau retry otomatis berbayar. Jika gagal, matikan live kembali dan periksa hasil sebelum meminta attempt baru. Source siap dikonfigurasi tidak berarti live sudah pernah diuji di hosting.

## 7. Backup, update dan rollback

Backup sebelum migration/update: source release/checksum, database demo konsisten melalui fitur backup panel atau mysqldump terverifikasi, APP_KEY/.env/credential secara privat. Jangan taruh dump atau key dalam document root. Restore harus diuji pada database QA terpisah; prosedur ini belum diuji pada provider.

Pisahkan user DB untuk migration/provisioning (DDL diperlukan) dari user runtime DML bila provider mendukung; runtime hanya butuh SELECT/INSERT/UPDATE/DELETE pada DB demo. Jangan grant global/root. Nama database harus tetap sama dengan policy bila user koneksi diganti.

Rollback awal: matikan kedua flag Groq, cache ulang, batasi akses/maintenance bila perlu. Arahkan current/document root ke release sebelumnya yang kompatibel setelah backup. Simpan histori dan counter, jangan migrate:fresh atau reset budget. Migration descriptive down sengaja menolak data null; history migration down bersifat merusak. Utamakan forward fix atau restore backup privat yang sudah direview, bukan down otomatis.

Jangan ganti APP_KEY pada update; session/enkripsi/backup harus memakai key instalasi yang sesuai. Jika kredensial bocor, revoke/rotate melalui provider setelah tindakan disetujui. DNS, domain existing dan deployment hanya setelah akses dan izin tersedia.
