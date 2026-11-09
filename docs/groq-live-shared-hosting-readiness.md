# Groq live pada shared hosting — analisis kesiapan UniNalar

Tanggal audit: 9 November 2026. Workspace: `/home/hendra_huang/projects/UniNalar-main`. Branch `main`, HEAD `fca168c`. Tujuan: demo sekolah dengan dataset sintetis, bukan pengolahan data mahasiswa kampus nyata.

## 1. Kesimpulan

**NO-GO untuk live pada shared hosting dengan source sekarang. CONDITIONAL GO untuk rencana implementasi di bawah**, setelah perubahan source, mock test, staging pada engine database target, dan persetujuan terpisah untuk smoke live terbatas. Mengisi API key dan mengaktifkan flag saja tidak cukup.

Tidak perlu SDK baru, Docker, WSL, queue worker, atau background process untuk alur ini. Laravel HTTP client dan penyimpanan Eloquent existing dapat dipertahankan. Perubahan utama adalah kebijakan runtime production demo yang terpisah dari guard development, konsistensi readiness UI/backend, pemisahan reuse mock/live, pembatasan penggunaan publik, dan penanganan kegagalan penyimpanan.

**Definisi hasil baru:** request pertama untuk indikator/model/metode yang belum memiliki hasil live valid memanggil provider dan menyimpan baris baru. Klik ulang dengan indikator sama menggunakan hasil live tersimpan. Tidak perlu tombol paksa regenerasi atau panggilan berbayar pada setiap klik. Hasil mock tidak memenuhi syarat reuse live.

Audit ini hanya membaca source/konfigurasi nonrahasia dan bukti lokal. Tidak membaca `.env`, nilai key, credential, atau database. Tidak menjalankan aplikasi, tes, build, migration, seeder, HTTP Groq, instalasi, deployment, atau operasi Git mutatif. Hanya laporan ini dibuat. Laporan deployment sebelumnya dan `.agents/` sudah untracked sebelum audit ini.

## 2. Penyebab pembatasan saat ini

| Lapisan dan bukti source | Perilaku saat ini | Dampak di hosting |
| --- | --- | --- |
| `resources/views/dashboard/mahasiswa/analisis-risiko.blade.php` | Readiness mengharuskan environment `local/testing`, live flag, profil mahasiswa, key tersedia, URL persis, dan model persis `openai/gpt-oss-20b`. | Production selalu menonaktifkan tombol. JavaScript hanya memperkuat state UI, bukan otorisasi. |
| `app/Services/GroqAcademicAnalysis.php::generate` | Di luar testing, memanggil `WslDevelopmentIdentity::verify()`, lalu memeriksa ID mahasiswa dalam `synthetic_student_ids`. | Guard berjalan sebelum lock, fingerprint lookup, dan HTTP. POST reuse juga tertolak di production. |
| `app/Services/WslDevelopmentIdentity.php` | Manifest privat harus cocok dengan root workspace, tag, path SQLite; environment harus local, default DB SQLite, URL DB kosong, file nyata bukan symlink, dan koneksi aktual cocok lewat `PRAGMA database_list`. | Path/manifest WSL dan SQLite sengaja merupakan batas development; bukan kebutuhan transport HTTP Groq. |
| `app/Services/GroqAcademicClient.php::describe` | Di luar testing, live flag wajib; endpoint HTTPS fixed dan key wajib. Model lolos pola karakter/panjang, tidak dibatasi ke model tunggal seperti UI. | Klien sendiri tidak mensyaratkan SQLite/local. Mengubah UI saja tetap tidak cukup; mengubah service saja membuat tombol tetap disabled. |
| `config/services.php` | `GROQ_API_KEY`, `GROQ_MODEL`, `GROQ_BASE_URL`, `GROQ_LIVE_ENABLED`, default live false. | Perlu konfigurasi server dan kebijakan production eksplisit, bukan bypass environment. |
| `app/Http/Controllers/MahasiswaAiController.php` | RuntimeException menjadi pesan environment belum terverifikasi. | Guard production tertolak tanpa HTTP. Catch terlalu luas juga berpotensi menyebut kegagalan runtime/cache/database sebagai masalah environment. |

Testing melewati guard identitas dan syarat live flag klien, tetapi `Http::preventStrayRequests()` mencegah jaringan tidak dimock. Jangan memakai `APP_ENV=testing` pada hosting. Jangan mengganti `APP_ENV=local`, memalsukan manifest WSL, menghapus guard global, atau mengubah `WslDevelopmentIdentity` agar menerima semua database.

Readiness UI sekarang belum memverifikasi manifest/allowlist sehingga tombol local dapat aktif walaupun backend menolak. Model UI dan backend juga belum memiliki kebijakan kesiapan yang sama.

## 3. Proteksi yang sudah ada dan harus dipertahankan

- Route POST `dashboard.mahasiswa.analisis-risiko.ai` berada di grup `auth` dan `level:1`, dengan `throttle:6,1`. Form memakai CSRF dan persetujuan eksplisit. Owner, Dosen, dan Admin bukan pembuat analisis lewat route ini.
- Controller memperoleh mahasiswa dari user login melalui `MahasiswaOperationalData::context`, bukan ID dari browser. `mahasiswa_id` dan `indikator` dilarang dalam payload; semester divalidasi ada dan bukan kode `DEMO%`. Service hanya menghitung detail KRS disetujui milik mahasiswa itu. Semester tanpa mata kuliah disetujui tidak mengirim request baru.
- Payload backend hanya agregat: jumlah mata kuliah/nilai final, rata-rata nilai final, komposisi kehadiran, SKS, IPS dan IPK. Tidak mengirim nama, NIM, email, telepon, ID pengguna/profil, atau free text pengguna.
- Key hanya dibaca dari konfigurasi server dan dipakai pada Authorization backend. Browser melakukan POST ke Laravel, bukan ke Groq. View hanya membutuhkan boolean readiness; jangan meneruskan object konfigurasi/key ke HTML, JSON, JavaScript atau variabel `VITE_*`.
- Endpoint dibatasi ke `https://api.groq.com/openai/v1/chat/completions`, redirect tidak diikuti, TLS verification tidak dinonaktifkan. Pertahankan pembatasan ini untuk menghindari key terkirim ke host lain.
- Connect timeout 5 detik, total timeout 25 detik, satu attempt tanpa `retry()`, output cap 900 token. Lock mahasiswa/semester 90 detik dengan tunggu maksimal 1 detik; service membatasi dua attempt baru/menit/user. Reuse tidak memanggil provider atau menghabiskan limit service.
- Provider 401/403, 429, dan error lain menjadi pesan aman. ConnectionException termasuk timeout menjadi pesan tidak dapat dihubungi. Raw body provider tidak diteruskan. Respons harus finish `stop`, body maksimal 65.536 byte setelah diterima, JSON tepat dua field, penjelasan maksimal 1.200 karakter dan 1–3 rekomendasi maksimal 240 karakter. Batas body setelah penerimaan bukan streaming memory cap.
- Snapshot indikator diperiksa ulang setelah respons. Analisis dan seluruh rekomendasi dibuat dalam satu transaksi. Baris historis tidak di-update/ditimpa; skor dan kategori tetap null. Blade menampilkan teks dengan escaping dan provenance mock/live.
- GET dashboard, filter, riwayat, dan monitoring Owner hanya membaca hasil tersimpan. Key/live provider tidak diperlukan untuk membaca histori.

## 4. Risiko dan prioritas sebelum live publik

| Prioritas | Temuan | Tindakan minimum |
| --- | --- | --- |
| **BLOCKER** | Production/MySQL ditolak oleh dua guard di atas. | Tambah jalur kebijakan demo production eksplisit, tetap fail-closed, tanpa melonggarkan guard local. |
| **BLOCKER** | Query reuse hanya memeriksa fingerprint dan sumber `groq_deskriptif`, tidak memeriksa `ringkasan.transport`. Fingerprint tidak membedakan mock/live. | Reuse live hanya menerima hasil `transport=live`; mock tetap terlihat sebagai simulasi dan tidak menghalangi request live pertama. Jangan menghapus/mengubah hasil mock lama. |
| **BLOCKER untuk demo yang terbuka publik** | Batas dua/menit/user bukan batas total penggunaan/biaya. Tidak ada kuota global persisten. Lock per semester tidak menserialisasi user yang meminta semester berbeda; check limit lalu hit bukan reservasi kuota atomik. | Batasi akun demo yang boleh live, serialisasi reservasi per user/global, dan tambahkan batas total attempt persisten yang tidak hilang saat cache dibersihkan. Awali satu request global berjalan bersamaan untuk demo kecil. |
| **REQUIRED** | Tidak ada kebijakan hosting dataset/account allowlist yang terpisah dari manifest WSL. | Allowlist user ID server-side terikat ke dataset demo terpisah yang sudah direview. ID bukan bukti data sintetis; review dataset dan larang input data pribadi. Akun privileged jangan dibagikan publik. |
| **REQUIRED** | Provider dapat sukses tetapi write database gagal; timeout browser/PHP dapat menyembunyikan hasil yang sebenarnya selesai. | Pesan aman khusus kegagalan penyimpanan, rollback seluruh hasil parsial, tanpa retry provider otomatis. Instruksikan membuka riwayat sebelum mencoba lagi jika hasil tidak pasti. |
| **REQUIRED** | Snapshot dicek sebelum transaksi penyimpanan; masih ada celah perubahan data setelah pengecekan. Tidak ada unique DB fingerprint; cache lock adalah proteksi utama. | Simpan snapshot sebagai data saat analisis, jangan klaim selalu sama dengan data saat ini. Wajib uji konkurensi dan lock yang benar di hosting; jangan tahan transaksi database selama HTTP. |
| **REQUIRED** | Parameter/model yang pernah lolos lokal belum tentu masih tersedia untuk akun/provider saat staging. | Pertahankan model dan kontrak existing dahulu; satukan validasi model di backend/UI. Verifikasi dukungan aktual hanya pada fase live yang disetujui. |
| **REQUIRED** | Key/config cache/log bisa ikut terunggah atau error debug membuka detail. | Environment production, debug false, HTTPS, cookie aman, root `/public`, `.env`/storage/config cache privat. Logging hanya metadata aman, jangan raw exception/request/header/body. |
| **OPTIONAL** | Tidak ada unique index relational untuk dedup fingerprint; lookup JSON dapat melambat. | Kolom fingerprint/transport dan unique key mahasiswa+semester+metode/model/fingerprint+transport jika skala atau kebutuhan jaminan dedup meningkat. Perlu migration terpisah dan penanganan data legacy. |
| **OPTIONAL** | Request sinkron menempati slot PHP selama provider berjalan. | Background queue hanya jika kapasitas/latensi nyata memerlukannya; bukan prasyarat demo kecil. |

Kuota attempt membatasi jumlah panggilan, bukan jaminan nominal tagihan. Token usage bisa tidak tersedia saat timeout. Terapkan batas akun/provider jika tersedia setelah diverifikasi; jangan mengasumsikan provider mempunyai hard spending cap tertentu.

## 5. Rancangan solusi minimum yang direkomendasikan

### Kebijakan runtime tunggal

Buat policy/service readiness yang dipakai controller/service dan UI. Pisahkan pemeriksaan akses mahasiswa/dataset dari izin melakukan panggilan baru:

1. **Local:** tetap gunakan `WslDevelopmentIdentity::verify()` dan synthetic student allowlist existing. Tidak ada perluasan identitas WSL ke MySQL.
2. **Production demo:** perlu dua opt-in server, misalnya mode demo hosting khusus dan live flag; default keduanya false. Wajib environment production, driver mysql/mariadb yang direhearsal, profil milik user login, user termasuk allowlist nonkosong, dan konfigurasi provider valid. Gunakan DB demo khusus, bukan database operasional. Environment lain ditolak kecuali staging yang ditetapkan eksplisit dalam kebijakan dan diuji.
3. **Testing:** mock-only, `preventStrayRequests`, tidak menggunakan key nyata; tes harus dapat mensimulasikan policy production/local tanpa meniadakan proteksi HTTP palsu.
4. Readiness UI berasal dari policy yang sama, hanya boolean/pesan aman. Backend tetap otoritatif untuk POST buatan manual. Jangan menjalankan verifikasi SQLite untuk halaman GET production atau melakukan HTTP sebagai readiness probe.

Mode demo dan ID allowlist adalah konfigurasi server baru yang diusulkan, **belum ada di source**. Bukan otomatis aktif saat key terisi. Untuk cakupan sekolah, tidak perlu membuka live untuk seluruh role atau semua akun mahasiswa.

### Alur request dan penyimpanan

Urutan yang disarankan:

1. Auth, role, CSRF, validasi semester/persetujuan dan lookup profil milik user.
2. Policy akses dataset/runtime, kemudian lock mahasiswa/semester dan hitung indikator/fingerprint existing.
3. Cari hasil live valid dengan fingerprint sama; bila ada, kembalikan tanpa HTTP. Flag live OFF mematikan panggilan baru; GET histori selalu tetap tersedia. Jika reuse POST tetap dibolehkan saat OFF, UI/pesan harus menjelaskannya dan backend harus menguji semantik tersebut.
4. Untuk hasil baru: validasi live flag, key, endpoint/model, KRS disetujui. Reservasi kuota atomik persisten dan slot konkurensi sebelum memanggil provider, tanpa menyimpan key/payload ke ledger.
5. Panggil client existing satu kali. Tidak retry otomatis pada 429/timeout atau kegagalan penyimpanan.
6. Validasi respons, periksa ulang indikator, lalu transaksi singkat insert analisis+rekomendasi. Snapshot, sumber, metode, model, transport live, token usage tervalidasi dan timestamp tetap disimpan. Histori lama tetap utuh.
7. Redirect ke hasil tersimpan. Bedakan pesan sukses baru, reuse, kuota habis, provider tidak tersedia, data berubah, dan gagal menyimpan. Jangan tampilkan SQL/path/header/key/raw body.

Untuk demo terbuka publik, rekomendasi budget minimum adalah **satu tabel kecil ledger/counter demo** dengan key periode/event unik, jumlah attempt, dan batas server-side; reservasi menggunakan transaksi/row lock singkat. Hitung attempt sebelum HTTP dan jangan kembalikan kuota otomatis setelah timeout yang statusnya tidak pasti. Lock global/cache boleh membatasi request bersamaan, tetapi hard budget tetap di database agar cache clear tidak meresetnya. Ini membutuhkan satu migration baru pada tahap implementasi. Tidak perlu menyimpan prompt/respons atau identitas pribadi ke ledger. Batas numerik ditentukan pengelola sebelum live, bukan diasumsikan oleh audit ini.

Alternatif cache counter saja lebih sedikit file, tetapi hilang ketika cache dibersihkan dan tidak memenuhi hard budget demo publik. Jika akses hanya evaluator tertutup, pengurangan scope kuota harus merupakan keputusan eksplisit dengan risiko terdokumentasi, bukan default deployment publik.

## 6. Daftar perubahan source minimum

Nama file baru di bawah adalah rancangan, bukan file yang sudah dibuat.

| File | Perubahan yang direncanakan |
| --- | --- |
| `app/Services/GroqAcademicAnalysis.php` | Cabangkan guard melalui policy; pertahankan local identity; filter reuse live; koordinasikan budget/lock; pertahankan agregat, snapshot dan transaksi append-only. |
| `app/Services/GroqLivePolicy.php` — baru | Readiness dan otorisasi runtime demo production; validasi allowlist/config bersama, mode testing aman. |
| `config/services.php` | Tambah opt-in demo hosting, allowlist user dan batas attempt/konkurensi yang default nonaktif. Jangan hardcode key. |
| `app/Http/Controllers/MahasiswaAiController.php` | Pertahankan own-profile/consent; klasifikasikan kegagalan policy, provider, lock dan persistensi dengan pesan aman. Hindari catch RuntimeException generik yang menyamarkan penyebab. |
| `resources/views/dashboard/mahasiswa/analisis-risiko.blade.php` | Gunakan readiness bersama dan teks production demo; hilangkan syarat local hardcoded tanpa melemahkan policy backend. |
| `app/Http/Controllers/MahasiswaDashboardController.php` | Bila readiness dihitung di controller, kirim boolean/reason aman ke view; GET tetap tanpa provider. |
| `app/Services/GroqAcademicClient.php` | Samakan validasi model/config dengan policy; pertahankan URL fixed/TLS/no-redirect/no-retry dan validator. Pesan timeout boleh tetap aman generik, tetapi jelaskan kemungkinan timeout kepada pengguna. Jangan perluas endpoint arbitrer. |
| `app/Services/GroqDemoBudget.php` — baru | Reservasi attempt persisten, serialisasi dan fail-closed saat budget store gagal. |
| `database/migrations/<timestamp>_create_groq_demo_budgets_table.php` — baru | Counter/ledger budget unik dengan operasi atomik; bukan perubahan tabel hasil analisis. |
| `tests/Feature/GroqAcademicAnalysisTest.php` dan tes policy/budget baru | Tambah skenario production/MySQL/MariaDB/mock-live reuse/kuota/konkurensi/error persistensi; perbarui assertions readiness. |
| Dokumentasi konfigurasi, misalnya `.env.example` dan `docs/groq-ai-integration.md` | Contoh opt-in false/allowlist kosong, konfigurasi aman dan operasi budget. Tidak berisi nilai key/password nyata. |

Tidak perlu perubahan routes/role, JavaScript, model hasil analisis, atau guard `WslDevelopmentIdentity.php` hanya untuk mengaktifkan jalur baru. Client tidak memerlukan penulisan ulang transport atau SDK baru. Audit ini tidak mengimplementasikan satu pun perubahan di tabel.

## 7. Shared hosting dan konfigurasi produksi

| Kebutuhan | Penilaian / yang harus ditanyakan pada provider |
| --- | --- |
| PHP/Laravel | Project membutuhkan PHP `^8.2`, Laravel 12. Gunakan versi PHP dan extension yang cocok dengan lockfile; versi aktual hosting belum diperiksa. |
| HTTP client | `Illuminate\Support\Facades\Http` memakai Guzzle existing, kompatibel dengan request PHP biasa. Tidak membutuhkan binary WSL atau service Docker. |
| Extension transport | Pilih ext-curl aktif dengan `curl_exec` tidak dinonaktifkan, OpenSSL dan CA valid. Guzzle dapat memakai stream jika `allow_url_fopen` aktif, tetapi jangan menganggap fallback memiliki perilaku connect timeout identik. Untuk mempertahankan kontrak timeout, cURL perlu diverifikasi. |
| Database/teks | PDO/pdo_mysql dan mbstring diperlukan; extension standar Laravel tetap berlaku. Tidak perlu pdo_sqlite untuk jalur production yang dirancang. |
| Outbound | Provider mengizinkan DNS dan HTTPS TCP 443 ke `api.groq.com`, TLS terverifikasi tanpa proxy yang mencatat Authorization. Jangan melewati TLS verification untuk mengatasi error. |
| Timeout | Batas PHP/FPM/LiteSpeed/proxy/request harus lebih besar dari 25 detik HTTP + query/agregasi/lock/write/render. Anggaran 45–60 detik dapat dijadikan bahan konfirmasi, bukan syarat pasti atau bukti ukur. Batas 30 detik total mungkin terlalu sempit. `set_time_limit` bukan solusi untuk hard timeout provider. |
| Kapasitas | Satu request menempati worker PHP selama menunggu. Periksa entry-process/worker limit, concurrent requests, CPU dan I/O; mulai satu live global bersamaan. Cron/queue worker tidak diperlukan. |
| Cache/lock | File cache pada satu server perlu storage writable dan locking nyata. Database cache dengan tabel cache/cache_locks existing dapat menjadi opsi; tetap uji lintas proses. Jangan pakai array/null cache produksi. Multi-node perlu lock store bersama. |
| Release | Document root `/public`, vendor/build siap, storage dan bootstrap/cache writable hanya pihak sesuai. Akses CLI/Terminal/support dibutuhkan untuk persiapan migration/cache secara aman, bukan runner web publik. |

Rencana konfigurasi server: `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, key aplikasi khusus instalasi demo baru yang kemudian dipertahankan, session/cookie aman, DB demo MySQL/MariaDB, cache persisten, dan live OFF sampai smoke disetujui. API key hanya di secret/environment server atau `.env` di luar public root dengan permission tepat. Konfigurasi cache dapat berisi key sehingga harus privat; perubahan flag/key membutuhkan penyegaran konfigurasi server yang terkontrol. Jangan mengunggah `.env` lokal, manifest/credential WSL, log, QA artifacts, browser profile atau database SQLite.

Tidak ada konfigurasi aktual yang diubah atau nilai rahasia yang dibaca dalam audit ini. Ketersediaan model, limit dan harga provider tidak diverifikasi melalui internet.

## 8. Penyimpanan MySQL/MariaDB

**Tidak ada ketergantungan SQLite dalam insert hasil Eloquent itu sendiri.** Ketergantungan SQLite berada pada guard development. Kompatibilitas target tetap memerlukan pengujian schema dan JSON aktual.

- Migration dasar `2026_10_19_000000_create_academic_tables.php` membuat `analisis_risiko`: FK mahasiswa restrict delete, semester/user nullable dengan null-on-delete, JSON `ringkasan`, model varchar(50), timestamp analisis, serta indeks mahasiswa/waktu dan semester/kategori. `rekomendasi_intervensi` FK analisis cascade delete, judul varchar(255), deskripsi text, prioritas/status default dan FK faktor nullable. Batas output rekomendasi 240 karakter muat pada judul dengan utf8mb4.
- **Wajib** migration `2026_11_08_000000_allow_descriptive_academic_analysis.php`: skor dan kategori menjadi nullable. Tanpanya live deskriptif gagal insert. `down()` sengaja menolak rollback bila ada null; jangan menghapus hasil untuk memaksa rollback.
- JSON memuat sumber, fingerprint, metode, indikator, penjelasan, usage, transport dan klasifikasi `NEEDS SPECIFICATION`. Bukan raw response/credential; rekomendasi merupakan child rows. Tidak membuat skor/kategori risiko resmi dari teks AI.
- Transaksi membuat parent dan seluruh child atau membatalkan semuanya. HTTP terjadi sebelum transaksi, sehingga sukses provider tidak dapat dibatalkan bila database gagal. Foreign key hilang, tabel/migration belum siap, permission insert, lock/deadlock, disk/quota, encoding atau JSON invalid dapat menggagalkan penyimpanan. Jangan retry seluruh service setelah provider sukses secara otomatis.
- MySQL JSON native dan MariaDB JSON memiliki implementasi berbeda. Query Laravel `ringkasan->fingerprint`, `->sumber` dan filter baru `->transport` harus diuji untuk string/null/missing key di engine target. Jangan menyalin DDL SQLite mentah. Gunakan InnoDB, strict mode, utf8mb4 dan FK aktif.
- SQLite tidak membuktikan row locking/konkurensi/DDL MySQL. Angka agregat dan fingerprint perlu stabil setelah pembacaan berulang pada engine target; tipe cast/rounding jangan diasumsikan identik lintas engine. Rehitung hanya sesuai metode yang terversi, jangan mengedit fingerprint histori.
- Rehearsal seluruh **sembilan migration existing** pada DB demo kosong, ditambah migration budget yang diusulkan jika diimplementasikan. Migration role `2026_10_19_000001` menjalankan CHECK khusus driver mysql dan `DROP CHECK` saat down; MariaDB melalui driver native dapat melewati CHECK, sementara sintaks down melalui driver mysql perlu diverifikasi. Ini risiko keseluruhan aplikasi, bukan alasan menghilangkan constraint.
- Tidak perlu perubahan struktur hasil analisis hanya untuk pindah driver. Budget baru membutuhkan tabel tersendiri; unique fingerprint relational adalah peningkatan opsional setelah kebutuhan konkurensi ditetapkan.

Database/demo initializer yang bebas manifest WSL tetap diperlukan menurut [audit deployment](uninalar-demo-deployment-readiness.md). Jangan menggunakan seeder development yang guarded production atau menonaktifkan filter data operasional: marker `DEMO` tertentu memang dikecualikan dari dashboard dan profil. Siapkan dataset sintetis terkurasi yang terhubung ke akun allowlist, KRS disetujui, nilai final/kehadiran yang konsisten, tanpa data privat.

## 9. Bukti existing dan batas keyakinan

- Source `tests/Feature/GroqAcademicAnalysisTest.php` mencakup HTTP fake, larangan stray request, kepemilikan/role, consent dan foreign ID, agregat tanpa PII, reuse/invalidation, provider 401/403/429/500/503, connection failure, output invalid/truncated, lock/rate limit, serta penolakan runtime tidak terverifikasi. Tes existing melewati guard di environment testing; **bukan bukti production diizinkan**.
- `qa-artifacts/groq-live/phpunit.log`: **139 tests / 2.356 assertions PASS**, PHP 8.2.34, historis lokal SQLite. `qa-artifacts/groq-live/groq-mocked.log` justru mencatat percobaan awal **10 tes, 1 error route Owner**; jangan mengutipnya sebagai pass terpisah. Source tes sekarang memakai route `.index`, dan suite penuh historis lulus.
- [Dokumentasi integrasi existing](groq-ai-integration.md) mencatat tepat satu smoke live sukses, tersimpan dan tampil pada Mahasiswa/Owner, dengan hasil mock tetap berbeda. Itu bukti historis development terverifikasi, **bukan izin request baru atau bukti hosting/MySQL**. Tidak mengulang smoke atau membaca ledger privat.
- Bukti MySQL lama pada [audit deployment](uninalar-demo-deployment-readiness.md) mendahului migration deskriptif November 8 dan username November 9. Belum ada sertifikasi live Groq + schema checkpoint ini pada MySQL/MariaDB hosting.

Tidak ada tes baru dijalankan selama audit. Penilaian risiko konkurensi, model/parameter/provider dan host timeout merupakan hasil pembacaan source, bukan simulasi atau pengukuran hosting baru.

## 10. Rencana pengujian dan gerbang GO

### A. Mock lokal terisolasi setelah implementasi

Gunakan dependency existing, DB tes terpisah, key sintetis dan `Http::preventStrayRequests()` pada semua tes; jangan environment/database utama. Jalankan ulang suite terdampak, bukan seluruh QA tanpa alasan.

1. Production default OFF; key saja, flag saja, allowlist kosong, user luar allowlist, driver/environment tidak diizinkan semuanya tidak mengirim HTTP. Readiness UI sesuai hasil policy. Flag OFF tetap mengizinkan GET histori.
2. Production demo lengkap + allowlisted owner-profile + consent + KRS valid menghasilkan tepat satu fake request dan satu parent dengan 1–3 child. Local tetap menolak identitas tidak cocok; tidak ada fallback ke jalur production ketika guard local gagal.
3. Guest/role lain, cross-student payload, indikator client-side dan semester invalid ditolak sebelum HTTP. Respons/tampilan tidak mengandung key atau provider body; output HTML/script tampil sebagai teks aman.
4. Mock lama dengan fingerprint sama tidak memenuhi reuse live; hasil live valid digunakan ulang tanpa HTTP. Perubahan indikator/model/metode menghasilkan record baru, histori lama identik; navigasi/GET tidak memanggil provider.
5. Uji 401/403/429/5xx, timeout, JSON invalid/truncated/oversize dan perubahan data saat HTTP: pesan aman, tanpa hasil parsial, tanpa retry. Uji response valid lalu gagal insert child: transaksi rollback, pesan kegagalan simpan aman.
6. Uji kuota global/per-user, persisten setelah cache clear, konkurensi beda semester/akun, held lock, habis slot, dan budget-store unavailable. Tidak ada panggilan bila reservasi gagal; batas tidak terlampaui dalam uji paralel.

### B. Staging engine dan hosting target, tetap mock

- DB demo terpisah, schema terkini dan akun uji khusus, konfigurasi menyerupai production dengan provider fake-only. Jangan memasang route untuk mengaktifkan mock atau menjalankan migration secara publik.
- Uji MySQL dan/atau versi MariaDB yang benar-benar akan dipakai, JSON lookup/cast, null skor/kategori, rekomendasi Unicode maksimum, FK, transaksi gagal, dedup, reservasi kuota serta cache lock lintas proses.
- Periksa PHP extensions/transport, private root, HTTPS/cookie/CSRF, permission, config cache dan larangan akses file privat. Mock latency/error di lingkungan staging aman untuk menguji batas PHP/proxy; ini belum membuktikan konektivitas Groq nyata.
- Catat HTTP/log metadata aman tanpa header/body/key dan tanpa dump database. Koneksi outbound/model tetap belum terbukti sampai smoke live disetujui.

### C. Live terbatas — hanya setelah persetujuan pengguna

Persetujuan harus menetapkan host/akun/dataset, model, batas attempt dan anggaran. Rekomendasi smoke awal: **maksimal satu attempt baru**, satu mahasiswa sintetis allowlisted, consent eksplisit, tanpa retry/health probe Groq atau model listing tambahan. Pastikan belum ada hasil live matching yang membuat smoke hanya reuse; jangan menghapus histori untuk memaksa request.

Verifikasi satu POST menghasilkan request backend, respons valid dan parent/child tersimpan, kemudian GET mahasiswa dan Owner terbaca tanpa call baru. Uji reuse tanpa mengirim ulang ke provider. Catat jumlah attempt, status, durasi dan usage aman. Jika gagal/timeout, jangan otomatis mencoba lagi; periksa hasil/ledger lalu minta keputusan baru jika butuh attempt tambahan. Matikan live setelah jendela demo/smoke selesai.

**GO live terbatas hanya jika** seluruh BLOCKER selesai, mock/staging target lulus, konfigurasi production aman, provider mengizinkan outbound/timeout, budget dan kill switch terbukti, dan pengguna menyetujui smoke. Kualitas bahasa AI tidak mengesahkan penilaian risiko akademik.

## 11. Rollback dan operasi demo

1. Kill switch: matikan live flag server dan perbarui config cache secara terkontrol; hentikan panggilan baru, biarkan histori GET tersedia. Tidak mengubah local development untuk mematikan hosting.
2. Tetap simpan budget/reservasi, analisis dan rekomendasi yang sudah tersimpan. Jangan reset budget untuk retry tanpa keputusan pengelola. Request yang sudah berjalan bisa selesai; cek riwayat sebelum menyimpulkan tidak tersimpan.
3. Jika source harus dikembalikan ke checkpoint, gunakan release sebelumnya yang kompatibel dengan schema nullable existing; tidak perlu menjalankan migration down. Source `fca168c` akan kembali memblokir production live tetapi tetap membaca histori.
4. Jangan rollback migration deskriptif saat null sudah ada, atau menghapus tabel budget saat request aktif. Back up database demo secara privat sebelum release; pemulihan backup hanya keputusan terpisah bila benar-benar diperlukan.
5. Bila ada indikasi key bocor, nonaktifkan live dan rotasi/revoke key di provider melalui tindakan yang disetujui; jangan menampilkan key di laporan/log. Kegagalan biasa tidak memerlukan mengganti APP_KEY atau membangun ulang data akademik.

**Estimasi cakupan:** perubahan source terarah dengan kompleksitas sedang, bukan sekadar mengganti satu flag. Belum terverifikasi: runtime hosting nyata, engine/versi DB target, hard timeout/worker limit, outbound/TLS dan dukungan model/parameter pada akun Groq saat deployment. Rencana ini menjaga development existing sambil membuka jalur production demo secara eksplisit; implementasinya belum dilakukan.
