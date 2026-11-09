# Runtime WSL terisolasi dan integrasi Groq

> Path `/path/to/UniNalar-main`, `~`, `<windows-user-home>` dan `<runtime-tag>`
> adalah placeholder publik pengganti identitas mesin pada bukti lokal. Ganti sesuai
> workspace/runtime development yang sudah diverifikasi sebelum memakai contoh perintah.

> Pembaruan autentikasi dan fixture Hendrik/Jovian beserta QA terbaru ada di
> [autentikasi dan fixture development](development-authentication-fixtures.md).
> Angka QA pada laporan integrasi awal di bawah merupakan baseline sebelumnya.
> Hasil mock terbaru menyimpan transport=mock dan diberi label simulasi.

Implementasi 8 November 2026. **First live smoke test: PASS, tepat satu request
HTTP 200, hasil tersimpan dan terbaca oleh Mahasiswa/Owner.** Mode live kembali
nonaktif; lihat [laporan live pertama](#first-live-groq-smoke-test--playwright--chrome-devtools)
di bawah. Uraian integrasi awal dan angka QA sebelumnya adalah baseline historis.
Tidak ada klasifikasi risiko resmi, formula, bobot, atau threshold baru.

## Runtime dan identitas database

Source: `/path/to/UniNalar-main`. PHP native WSL tidak tersedia;
image existing `uninalar-app:latest` menyediakan PHP 8.2.34, PDO SQLite/MySQL,
mbstring dan curl. Tidak ada instalasi PHP, SDK, package atau image baru.

Runtime baru:

- Container `uninalar-wsl-dev-<runtime-tag>`, UID/GID pengguna workspace,
  capability dihapus dan no-new-privileges.
- Network bridge khusus `uninalar-wsl-dev-<runtime-tag>-net`; tidak bergabung
  dengan network/container MySQL operasional. Network ini dapat mengakses keluar;
  pencegahan live API berada pada konfigurasi dan guard aplikasi, bukan firewall.
- Bind source WSL pada path absolut yang sama di container. Tidak ada named volume,
  salinan database operasional atau perubahan Compose operasional.
- PHP development server di container port 8000, dipublikasikan hanya pada
  `127.0.0.1:8140`. Akses browser Windows: **http://localhost:8140**.
  Windows HTTP dan Chrome telah diverifikasi; localhost WSL tidak dapat mengakses
  port publik Docker Desktop pada pemeriksaan ini. CLI dijalankan melalui Docker.
- SQLite baru:
  `/path/to/UniNalar-main/database/wsl-dev-<runtime-tag>.sqlite`.

Database dibuat dengan exclusive-create, permission 0600, dan nama acak. Sebelum
mutation, pemeriksaan SQLite read-only memastikan path aktual PRAGMA database_list
sama dan sqlite_master kosong. Setelah Laravel boot, guard kembali mensyaratkan
APP_ENV local, driver SQLite, DB_URL kosong, path absolut sesuai manifest/tag,
file bukan symlink dan identitas koneksi SQLite aktual sama, **sebelum migration**.
Migration hanya pada file baru tersebut. Fixture sintetis baru dibuat setelah guard
ulang dan pemeriksaan tabel users kosong. Tidak ada query/koneksi MySQL operasional.

Manifest lokal `storage/app/private/wsl-dev-runtime.json` mencatat identitas dan
ID mahasiswa fixture sintetis. Kredensial login fixture disimpan lokal pada
`storage/app/private/wsl-dev-credentials.json` (0600); nilainya tidak dicetak atau
disalinkan ke dokumentasi. Keduanya ignored. Fixture memakai helper tes existing,
bukan salinan profil mahasiswa nyata. Jangan menambahkan data nyata ke DB ini.

Empat container operasional lama tetap berjalan; sumber Windows, port 8080/8081,
MySQL host 3308 dan volume persistennya tidak diubah. Percobaan awal network
internal tidak menerbitkan port pada Docker ini; hanya container/network development
baru yang diganti dengan bridge terpisah. File SQLite tetap dipertahankan.

## Environment lokal dan cara menjalankan

`.env` WSL kini memakai APP_NAME UniNalar, local/debug=true, URL localhost:8140,
locale id/fallback en, SQLite dengan path di atas, session/cache file dan queue
sync. DB_URL serta parameter MySQL dinonaktifkan/kosong. APP_KEY baru dibuat hanya
setelah dipastikan key sebelumnya kosong dan DB development baru belum berisi data.
Baris Groq key dipertahankan sebagai data opaque tanpa mencetak atau mengambil
nilainya untuk pemeriksaan. Pemisah baris terakhir environment diperbaiki agar
variabel baru tidak tersambung ke base URL. `.env` tetap ignored dan tidak tracked.

Runtime saat ini sudah dibuat dan diinisialisasi; **jangan mengulang prepare/init
untuk mereset data**. Perintah pemeriksaan/menjalankan kembali container yang sama:

```sh
cd /path/to/UniNalar-main
docker ps --filter "name=uninalar-wsl-dev-<runtime-tag>"
docker start "uninalar-wsl-dev-<runtime-tag>"  # hanya jika berhenti
```

Untuk workspace baru yang benar-benar belum disiapkan, alur reproducible adalah:

```sh
python3 scripts/wsl-dev.py prepare
python3 scripts/wsl-dev.py start
# Nama container baru ditampilkan oleh start; gunakan nama itu pada exec:
docker exec <container-development-baru> php scripts/wsl-dev-init.php
docker exec <container-development-baru> php scripts/wsl-dev-fixtures.php
```

Prepare menolak manifest existing, key existing, cached config dan port yang sudah
terpakai; initializer menolak database tidak kosong. Perintah ini khusus environment
baru, bukan prosedur migration produksi. Hindari composer setup, composer dev,
artisan migrate/seed tanpa guard, dan jangan menyalin .env.docker.example yang
mengarah ke uninalar. Script tidak memakai sudo. Akses Docker memerlukan izin socket
Docker Desktop pada sesi yang digunakan; gunakan integrasi/izin Docker yang sah,
bukan chmod 777, perubahan owner massal atau sudo sembarangan.

## Konfigurasi Groq dan arsitektur

`config/services.php` membaca GROQ_API_KEY, GROQ_MODEL, GROQ_BASE_URL dan
GROQ_LIVE_ENABLED. `.env.example` hanya memuat key kosong dan default non-secret:

```dotenv
GROQ_MODEL=openai/gpt-oss-20b
GROQ_BASE_URL=https://api.groq.com/openai/v1
GROQ_LIVE_ENABLED=false
```

Laravel HTTP Client bawaan memanggil chat/completions dengan JSON output,
reasoning_effort low, max_completion_tokens 900, connect timeout 5 detik dan total
25 detik. Tidak ada SDK tambahan, redirect provider atau retry otomatis. Base URL
harus tepat endpoint HTTPS Groq; key tidak dikirim ke host lain atau browser.

Referensi implementasi: [API Groq](https://console.groq.com/docs/api-reference) dan
[structured outputs](https://console.groq.com/docs/structured-outputs).
JSON mode tetap divalidasi secara lokal, bukan dianggap jaminan semantik.

`GroqAcademicAnalysis` mengambil hanya agregat mata kuliah KRS disetujui, jumlah
nilai final, rata-rata nilai final dan hitungan status kehadiran yang dikenal.
Tidak mengirim nama, NIM, email, nomor telepon, nama mata kuliah, catatan bebas,
ID mahasiswa/user/semester atau teks rekomendasi lama. Nilai draft dan KRS yang
bukan peserta resmi tidak digunakan. Rata-rata numerik merupakan indikator
deskriptif, bukan formula risiko atau pengganti IPS/IPK resmi.

POST `/dashboard/mahasiswa/analisis-risiko/ai` memerlukan auth role Mahasiswa,
CSRF, semester valid dan persetujuan eksplisit. Pemilik selalu dari session;
mahasiswa_id/indikator dari browser ditolak. Di luar testing, guard identitas
SQLite wajib lulus dan mahasiswa harus tercatat sebagai fixture sintetis pada
manifest. Data nyata tidak memperoleh jalur pengiriman. Di luar testing,
GROQ_LIVE_ENABLED=false menolak request provider baru.

Login, dashboard refresh, halaman risiko dan seluruh GET Owner tidak memanggil
provider. Owner tetap baca-saja; route mutasi AI tidak tersedia untuk Owner,
Dosen atau Admin. Akses mahasiswa lain tetap ditolak/discope existing.

## Penyimpanan, token dan konkurensi

Tidak ada tabel/workflow analisis duplikat. Hasil disimpan pada analisis_risiko,
rekomendasi pada rekomendasi_intervensi existing (maksimal tiga). ringkasan JSON
menyimpan indikator, penjelasan, fingerprint, sumber groq_deskriptif, versi metode,
metadata token dan status NEEDS SPECIFICATION; row juga mencatat model, aktor dan
waktu analisis. Setiap perubahan input/model/metode membentuk riwayat baru.
Fingerprint SHA-256 mencakup agregat yang dikirim, model dan versi prompt/metode.
Jika prompt berubah, bump descriptive-v1 agar hasil lama tidak direuse keliru.

Hasil valid digunakan kembali berdasarkan mahasiswa + semester + fingerprint.
Cache lock 90 detik per mahasiswa/semester mencegah panggilan bersamaan, dengan
waktu tunggu 1 detik dan pesan sedang diproses jika terkunci. Pemeriksaan reuse
berada di dalam lock; indikator diperiksa ulang sebelum menyimpan agar hasil dari
data yang berubah tidak diterima. Rate limit service dua percobaan baru per menit
per akun, ditambah middleware enam POST per menit. Reuse hasil tidak membayar token
lagi. Tidak ada batch, refresh otomatis atau retry saat kuota habis.

Lock file memerlukan storage bersama pada runtime yang menggunakan DB sama.
Topologi multi-host/distributed belum didukung atau diuji; perlu strategi lock
bersama sebelum deployment. Fixture concurrency lintas dua proses lulus: satu hasil,
satu panggilan provider mock, pesaing menerima busy, retry menggunakan hasil sama.

Respons wajib JSON dengan tepat penjelasan maksimal 1.200 karakter dan 1–3
rekomendasi maksimal 240 karakter. Respons terlalu besar (>64 KiB), terpotong,
field tambahan/tipe salah dan JSON invalid ditolak tanpa penyimpanan parsial.
Output ditampilkan dengan escaping Blade, bukan HTML dari model. Metadata token
hanya integer terbatas; body error, credential dan payload pribadi tidak dilog.
401/403, 429, timeout dan 5xx menjadi pesan Indonesia aman. Guard runtime gagal
juga memberi pesan aman tanpa detail konfigurasi.

## Schema dan batas kebijakan akademik

Migration baru hanya membuat skor dan kategori analisis existing boleh null,
sehingga hasil deskriptif tidak memalsukan angka nol atau kategori resmi. Data
historis dipertahankan; rollback menolak null rows sampai direview, bukan mengisi
nilai rekaan. Migration diterapkan pada SQLite development baru dan SQLite testing;
**belum diterapkan atau diuji pada MySQL operasional**.

NEEDS SPECIFICATION: klasifikasi/threshold/bobot resmi, metode validasi dan mutu
AI, otorisasi penggunaan data nyata, retensi/provider data processing, pemetaan
Kaprodi ke prodi, workflow tindak lanjut serta input/koreksi kehadiran. Integrasi
ini tidak membuat nilai/faktor risiko berbobot, tidak mengambil keputusan akademik
dan tidak mengaktifkan pengiriman data nyata.

## UI dan QA

Halaman analisis Mahasiswa existing memuat persetujuan, pilihan semester, status
loading/error, indikator tersimpan, penjelasan, rekomendasi, waktu dan batas AI.
Tombol request baru dinonaktifkan selama live API belum diaktifkan. Owner membaca
penjelasan tersimpan tanpa tombol generate. Null skor/kategori ditampilkan jelas,
termasuk ringkasan mahasiswa, daftar Owner dan distribusi kategori Owner.

Bukti lokal di `qa-artifacts/groq/` (ignored, bukan bagian source commit):

- Regresi SQLite final: **129 tes / 2183 assertions PASS** (sqlite-final.log).
- Tes terarah akhir Groq + keamanan route: **10 tes / 262 assertions PASS**.
- PHP syntax **124 file PASS**; Pint **127 file PASS**; Vite **127 modules PASS**.
- Browser Chrome existing, tanpa instalasi: **10 capture** (Mahasiswa + detail
  Owner pada 375×812, 390×844, 768×1024, 1366×768, 1920×1080) dan **7 checks PASS**.
  Mencakup rendering hasil mock, label form, disabled state, reuse lewat CSRF form,
  penolakan CSRF dan empat path privat. Tidak ada pageerror atau request browser
  ke Groq; screenshot Mahasiswa mobile dan Owner desktop diperiksa visual.
- Concurrency dua proses dengan provider mock: **PASS**, satu panggilan dan
  retry memperoleh hasil yang sama. Busy merupakan hasil yang diharapkan, bukan
  alasan menjalankan request duplikat.
- MySQL **NOT RUN**: tidak tersedia instance QA MySQL terisolasi yang telah
  diverifikasi pada sesi ini; instance operasional sengaja tidak digunakan.
- Live Groq **NOT RUN**, key/quota/latency provider belum diverifikasi.

Bukan sertifikasi lintas browser/full WCAG, beban produksi atau keamanan menyeluruh.
Kesalahan awal harness (akumulasi HTTP fake, CSRF pada environment local dan
asumsi kedua proses harus langsung menerima hasil) diperbaiki tanpa melemahkan
kontrak; busy+retry dan CSRF sah diuji secara eksplisit.

## File implementasi

Baru: app/Http/Controllers/MahasiswaAiController.php;
app/Services/GroqAcademicClient.php, GroqAcademicAnalysis.php,
WslDevelopmentIdentity.php; migration 2026_11_08_000000_allow_descriptive_academic_analysis.php;
scripts/wsl-dev.py, wsl-router.php, wsl-dev-init.php, wsl-dev-fixtures.php;
tests/Feature/GroqAcademicAnalysisTest.php; dokumen ini.

Diubah: config/services.php, .env.example, routes/web.php,
MahasiswaDashboardController, OwnerDashboardController, CSS/JS dashboard,
view analisis/ringkasan Mahasiswa dan daftar/detail risiko Owner,
FinalSystemSecurityTest (85 endpoint akademik), README dan dua laporan audit/final
historis dengan penunjuk ke hasil terbaru. .env lokal diubah hanya untuk isolasi,
tetap ignored. Lockfile, Compose, container/volume operasional tidak diubah.

## Prosedur historis sebelum izin live

Sebelumnya belum ada izin live. Pengguna kemudian memberi izin eksplisit maksimal
satu request dengan data sintetis pada 8 November 2026. Prosedur yang benar-benar
dijalankan dicatat di bawah. `.env` tidak diubah untuk smoke test: live flag hanya
diaktifkan pada proses CLI terjaga, lalu dipulihkan dalam `finally`. Web runtime
tetap nonaktif sepanjang pengujian. Tidak menghapus histori mock atau mengubah
nilai akademik untuk memaksa request baru.

## First Live Groq Smoke Test — Playwright & Chrome DevTools

8 November 2026, **19:50:02 WIB** (12:50:02 UTC). **PASS untuk konektivitas dan
alur hasil deskriptif development. NEEDS SPECIFICATION untuk skoring risiko resmi.**
Izin pengguna: maksimal **satu** request live Groq, tanpa retry dan tanpa request
live tambahan apabila gagal. Angka di bagian ini adalah hasil pengujian baru,
bukan penggunaan ulang baseline 137 tes/2.317 assertions atau 300 viewport lama.

### 1. Runtime WSL

Container development yang semula berhenti dinyalakan kembali tanpa recreate:
`uninalar-wsl-dev-<runtime-tag>`. Inspect menunjukkan bind root
`/path/to/UniNalar-main` ke path yang sama, working directory
sesuai dan published port hanya `127.0.0.1:8140`. Browser Windows mengakses port
tersebut melalui localhost/127.0.0.1. Container/source Windows lama dan layanan
operasional tidak diubah. Tidak ada instalasi dependency atau perubahan Compose.

### 2. SQLite development dan fixture

`WslDevelopmentIdentity::verify()` lulus: APP_ENV local, driver SQLite, DB_URL
kosong, path manifest sesuai, bukan symlink, dan PRAGMA database_list aktual
cocok. Target tetap `database/wsl-dev-<runtime-tag>.sqlite` yang sudah diinisialisasi.
Tidak menjalankan migration, seeder atau query MySQL. Fixture Hendrik tercatat
pada synthetic allowlist dan terhubung ke akun Mahasiswa yang benar.

Snapshot konsisten sebelum write disimpan lokal/ignored dengan permission 0600.
Sesudah seluruh QA: baris lama pada 15 tabel akademik tetap identik; hanya ada
**1 analisis baru dan 3 rekomendasi baru** yang dimiliki analisis tersebut.
Profil, KRS, nilai final/draft dan kehadiran tidak berubah. Hash `.env` identik.

### 3. Model

Model yang dikirim: **`openai/gpt-oss-20b`**. Tidak mengubah model environment atau
mencoba model lain. Request menggunakan reasoning effort low, temperature 0,2 dan
JSON object mode sesuai integrasi existing. Dukungan parameter diperiksa pada
[dokumentasi API resmi Groq](https://console.groq.com/docs/api-reference) dan
[panduan reasoning](https://console.groq.com/docs/reasoning).

### 4. Endpoint dan klien

POST **`https://api.groq.com/openai/v1/chat/completions`**, melalui
`GroqAcademicAnalysis` → `GroqAcademicClient` → Laravel HTTP client existing.
Tidak membuat klien provider kedua. TLS verification aktif; redirect tidak diikuti.
Connect timeout 5 detik, total timeout 25 detik, output cap 900 token, satu attempt
Laravel (tanpa `retry()`). Tidak ada request models/healthcheck ke Groq.

### 5. Jumlah request live aktual dan consent

**Sebelum smoke: 0 tercatat dalam cakupan ini; sesudah: tepat 1 request live.**
Tidak mengklaim mengetahui seluruh histori akun Groq di luar workspace.
Sebelum eksekusi tidak ada hasil transport live dan ledger belum ada. Consent
sintetis eksplisit diverifikasi pada harness, akun/role/allowlist juga diperiksa.

Ledger atomik persisten `storage/app/private/groq-first-live-attempt.json`
(ignored, 0600) dibuat memakai exclusive-create sebelum transport. Ledger dianggap
terpakai termasuk bila koneksi gagal. Middleware memeriksa endpoint, method,
model, payload dan counter sebelum meneruskan request. Live flag hanya berubah
di memori proses CLI; `finally` mematikannya kembali. Konfigurasi web tidak pernah
diaktifkan. Ledger sekarang bernilai 1; jangan menghapus/reset untuk mencoba lagi.
Harness live tidak dijalankan ulang sesudah request pertama.

### 6. HTTP status

**200 OK**, finish reason **`stop`**. Tidak ada retry, redirect atau request live
kedua. Bukti berasal dari middleware HTTP server-side yang mengamati respons
provider, bukan dari network browser. Header Authorization, key, raw response dan
trace exception bercredential tidak dicatat.

### 7. Payload dan validitas respons

Payload hanya agregat numerik berikut, dihitung dari database fixture yang
terverifikasi sebelum request:

| Indikator | Nilai |
| --- | --- |
| Mata kuliah disetujui pada semester | 5 |
| Nilai final relevan | 4 |
| Rata-rata nilai angka final | 87 |
| SKS disetujui pada semester | 16 |
| IPS semester | 3,63 |
| IPK kumulatif saat analisis | 3,26 |
| Kehadiran tercatat | 38 hadir, 0 terlambat, 1 izin, 1 alpha |

IPS/IPK memakai `AcademicGradeSummary` existing; SKS mengikuti detail KRS yang
disetujui. Nilai draft dan mahasiswa lain tidak masuk nilai final/IPS/IPK.
Tidak mengirim nama, username, NIM, email, nomor telepon, ID profil atau free text
mahasiswa. Key hanya dipakai sebagai autentikasi provider, bukan isi prompt.

Respons lolos JSON parsing dan validator existing: tepat `penjelasan` string
maksimal 1.200 karakter dan `rekomendasi` berupa list 1–3 string maksimal 240
karakter/item; output tidak terpotong. Tiga rekomendasi diterima. Penjelasan
bersifat deskriptif umum dan menyebut keterbatasan konteks, bukan skor resmi.
Tidak menganggap schema valid sebagai bukti validitas penilaian akademik.

### 8. Token usage

Metadata usage yang tersimpan: **278 prompt tokens + 248 completion tokens = 526
total tokens**. Ini usage respons Groq, bukan estimasi biaya atau asumsi batas
900 token terpakai seluruhnya. Tidak ada request tambahan untuk memperoleh usage.

### 9. Penyimpanan

Analisis **ID 5**, source `groq_deskriptif`, transport **`live`**, model sesuai
request dan metode **`descriptive-v2`**. Menyimpan fingerprint, indikator,
penjelasan, usage dan timestamp; tiga rekomendasi terhubung dalam transaksi yang
sama. `skor` dan `kategori` tetap null, klasifikasi `NEEDS SPECIFICATION`.
Hasil simulasi lama ID 4 dan histori legacy tetap utuh.

Metode v2 menambahkan IPS/IPK/SKS ke payload dan fingerprint. Hasil mock v1 tidak
disamarkan sebagai hasil live atau dihapus demi membuat request baru. Perubahan
angka agregat/metode menyebabkan fingerprint baru secara wajar.

### 10. Tampilan Mahasiswa

Login fixture Hendrik, dashboard, analisis semester, rekomendasi, refresh,
kembali ke dashboard lalu kembali ke analisis dan logout diperiksa. Penjelasan
serta tiga rekomendasi hasil live tampil. Indikator saat analisis sesuai tabel
di atas; timestamp tampil **08/11/2026 19:50 WIB**. Hasil live diberi label
“Hasil live Groq tersimpan”; hasil mock tetap memiliki label simulasi yang berbeda.
Tombol analisis baru kembali disabled karena mode live nonaktif.

### 11. Tampilan Owner/Kaprodi

Login fixture Owner, dashboard, filter semester, daftar risiko, tautan detail
hasil live, detail mahasiswa dan logout diperiksa. Daftar dan detail menampilkan
provenance live, penjelasan tersimpan dan waktu WIB yang sama. Owner tetap
baca-saja; tidak ada form mutatif di main content dan POST AI ditolak 403.

### 12. Playwright

**45 kasus halaman/viewport**, **98 assertion browser/interaksi** dan **7 HTTP
checks** PASS. Menggunakan Chrome existing via Playwright cached tanpa instalasi.
Akun Mahasiswa, Owner, Dosen, Admin dan mahasiswa sintetis lain diuji. Screenshot
hanya setelah login atau pada halaman tanpa password; credential diteruskan melalui
stdin lokal, tidak ditulis ke harness JS/HTML, log atau screenshot.

Putaran awal seluruh 96 assertion dan 7 HTTP checks lulus, tetapi counter aset
error-state belum direset sesudah beberapa navigasi sehingga memberi duplikasi
palsu. Harness diperbaiki agar menghitung per navigasi, ditambah dua assertion
redirect reuse sukses. Putaran final 98 assertion dan semua 45 kasus lulus.
Tidak mengubah source aplikasi untuk menyembunyikan temuan harness.

### 13. Chrome DevTools

Chrome **156.0.8078.81**, **DevTools Protocol melalui Playwright** (Network,
Runtime, Performance), bukan klaim DevTools MCP/manual. Putaran final: **0 console
error/warning, 0 exception, 0 failed local requests, 0 asset duplication per
navigasi dan 0 browser request ke api.groq.com**. Tidak ditemukan pola key Groq
pada HTML. Pemeriksaan source/build dengan key aktual hanya menghasilkan count
kecocokan, tanpa menampilkan key: **0 kecocokan**.

CLS teramati maksimum 0 pada jendela pengukuran lokal setelah font siap dengan
reduced motion. Ini bukan metrik lapangan/Core Web Vitals produksi. Screenshot
desktop hasil live Mahasiswa, rekomendasi, detail Owner dan contoh mobile
error/empty state ditinjau; bukan klaim semua piksel/perangkat telah diperiksa.

### 14. Responsivitas

375×812, 390×844, 768×1024, 1366×768, 1920×1080. Sembilan state/halaman × lima:
dashboard Mahasiswa, risiko, rekomendasi, error validasi consent, analisis kosong
pada semester historis, dashboard Owner, daftar risiko, detail analisis dan detail
mahasiswa. Seluruhnya 200 tanpa overflow dokumen, ID duplikat, label kontrol
hilang, referensi ARIA putus atau gambar rusak. Error state menggunakan validasi
nyata tanpa memanggil provider. Kegagalan provider diuji melalui fake di regresi.

### 15. Reuse, rate limiting dan efisiensi

Dua POST identik pada web dengan consent menghasilkan redirect sukses ke hasil
tersimpan. Probe backend tambahan dengan HTTP fake fail-closed mengembalikan
**ID 5 yang sama, nol provider calls**, mode live false, ledger tetap 1.

Tes backend dengan HTTP fake membuktikan login, dashboard/refresh, navigasi,
filter semester, Owner dan reuse tidak mengirim HTTP ke provider. Rate limit
2 attempt/menit per user dan lock mahasiswa/semester diuji; lock yang ditahan
mencegah request duplikat, attempt ketiga ditolak setelah dua kegagalan mocked.
Provider 401/403/429/500/503 masing-masing satu request mocked tanpa retry;
timeout/invalid/truncated output ditolak tanpa persistensi. Browser network saja
bukan dasar klaim hemat token atau jumlah request live.

### 16. Authorization

Mahasiswa sintetis lain tidak dapat melihat penjelasan live Hendrik pada halaman
miliknya, dan akses detail Owner ditolak 403. Dosen/Admin/Owner POST ke AI
Mahasiswa ditolak 403. Guest sesudah logout mendapat redirect login pada route
terlindungi. Missing consent ditolak sebelum provider. Manipulasi student ID,
indikator client-side dan semester invalid juga tercakup mocked tests. Tidak ada
perubahan aturan role atau pemisahan data.

### 17. Regresi

| Pemeriksaan baru | Hasil |
| --- | --- |
| PHPUnit SQLite :memory: | **139 tes / 2.356 assertions PASS** |
| Subset Groq mocked | Termasuk pada suite penuh; tidak ada live request di tes |
| Browser | 45 kasus viewport + 98 assertions PASS |
| HTTP reuse/consent/authz | 7/7 PASS |
| Pint | 136 file PASS |
| PHP syntax | 137 file PASS |
| Vite production build | PASS |
| Git diff whitespace | PASS |
| MySQL QA | NOT RUN; tidak ada instance QA terisolasi yang diverifikasi |

Authentication, authorization, KRS, nilai, finalisasi, IPS/IPK, rendering hasil dan
rekomendasi tetap tercakup. Dua tes baru melindungi agregat nilai serta jalur
navigasi/reuse tanpa provider. Assertion existing dipertahankan/diperkuat.

### 18. Perbaikan pada sesi ini

- Payload melengkapi IPS/IPK/SKS dari service akademik existing, tanpa data pribadi.
  Fingerprint/metode v2 menyertakan kontrak baru; tidak mengubah formula akademik.
- Komponen `ai-provenance` membedakan live/simulasi pada analisis Mahasiswa,
  rekomendasi, serta daftar/detail risiko Owner. Tidak menambah aksi AI baru.
- Indikator yang sudah tersimpan ditampilkan pada analisis Mahasiswa secara jujur.
- Tes provider error diperkuat dengan assertion satu request per status; dua
  regresi baru membuktikan isolasi agregat dan no-call pada navigasi/reuse.
- Koreksi harness/fixture tes: nama route Owner memakai `.index`, login melalui
  `/dashboard` sebelum redirect role, fixture draft terpisah menghormati kunci
  finalisasi; counter aset direset per navigasi. Tidak melemahkan proteksi nilai.

Tidak ada perubahan pada klien provider, `.env`, APP_KEY, lockfile, database
operasional, Docker volume atau konfigurasi production. Pedoman UI/UX existing
dipertahankan; tidak ada redesign.

### 19. Batas dan bukti

**Tidak ada blocker konektivitas pada smoke ini.** Satu respons sukses tidak
membuktikan availability, ketahanan provider, kualitas seluruh jawaban, billing,
atau kesiapan produksi. Failure handling, rate limit dan duplikasi menggunakan
mock; tidak diuji dengan request live kedua. Prompt/hasil deskriptif tidak
mengesahkan skor/kategori risiko atau keputusan intervensi.

Bukti lokal ignored di `qa-artifacts/groq-live/`: `smoke-summary.json`,
`reuse-summary.json`, `browser-summary.json`, 45 screenshot, `integrity.json`,
`before.sqlite`, `groq-mocked.log`, `phpunit.log`, `pint.log`, `php-syntax.log`,
`build.log`, harness dan hasil percobaan awal. Ledger ignored berada di storage
privat. Artefak bukan source aplikasi dan tidak di-stage; key/provider header
maupun password tidak dicatat. `.env` tetap ignored dan untracked.

### 20. Final verdict

**PASS — integrasi Groq nyata terbukti dari Laravel sampai penyimpanan dan
render Mahasiswa/Owner, dengan tepat satu request live.** Live kembali nonaktif,
reuse tanpa provider terbukti, data akademik lama dan `.env` tetap utuh.

**NEEDS SPECIFICATION — skoring risiko resmi.** Formula, bobot dan threshold
institusi belum ditetapkan; tidak ada klaim validasi risiko resmi. Tidak melakukan
staging, commit, push, deployment atau perubahan layanan operasional. Audit Git
manual sebelumnya perlu diperbarui sebelum commit karena source bertambah.
