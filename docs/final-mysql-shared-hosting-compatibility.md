# MySQL compatibility dan pre-hosting QA UniNalar

Tanggal: 9 November 2026. Branch `main`, HEAD tetap `fca168c`. Pengujian memakai working tree terkini, termasuk Groq production-demo dan koreksi regression test yang belum commit. Laporan ini menggantikan status preflight BLOCKED sebelumnya setelah pengguna menyetujui pembuatan QA sementara.

## 1. Keputusan

**PASS untuk cakupan pengujian pada MySQL 8.4.11; CONDITIONAL GO untuk persiapan shared hosting.** Migration, workflow akademik, Groq production-demo dengan HTTP fake, dan concurrency yang diuji berhasil pada server QA MySQL terisolasi. **MariaDB dan hosting aktual tetap NOT VERIFIED.** Tidak ada deployment atau request Groq live.

Tidak ditemukan bug kompatibilitas yang mengharuskan perubahan source aplikasi. Hanya test/support MySQL ditambahkan dan laporan ini diperbarui. Konfigurasi lokal, SQLite development, histori Groq, role internal dan perubahan working tree sebelumnya dipertahankan.

## 2. Lingkungan dan persetujuan QA

Preflight `docker ps -a` tidak menemukan container QA existing. `uninalar-db-1` memakai volume persisten operasional `uninalar_uninlar_mysql_data`; `servisa-db-1` merupakan proyek lain dan bukan alternatif QA. Keduanya tidak dipakai, dimulai ulang atau diubah.

Setelah persetujuan eksplisit pengguna, dibuat resource baru untuk run **`hosting_083d2729dca9`**:

| Komponen | Identitas / kondisi aktual |
| --- | --- |
| MySQL QA | `uninalar-qa-hosting_083d2729dca9-db`, server **8.4.11**, UUID `464bfbb4-dc4a-11f1-a23f-32197b6adb7b` |
| PHP QA | `uninalar-qa-hosting_083d2729dca9-php`, PHP **8.2.34** |
| Database | `qa_hosting_083d2729dca9`, dibuat baru pada server QA setelah pemeriksaan isolasi |
| Jaringan | `uninalar-qa-hosting_083d2729dca9`, **internal**, hanya kedua container QA tersebut |
| Datadir | `/var/lib/mysql` berupa **tmpfs**, tanpa volume database operasional |
| Port | Tidak ada published port MySQL ataupun PHP QA |
| Image | Image cached `mysql:8.4` dan `uninalar-app:latest`, keduanya dijalankan dengan `--pull=never` |
| Source | Salinan source terpilih pada staging `/tmp`, dipasang sebagai `/qa`; vendor existing dipasang read-only pada `/qa/vendor` |
| File privat | Tidak menyalin `.env`, SQLite utama, manifest/credential WSL, histori QA lama, browser profile atau data operasional |
| Kredensial | User `qa_runner` khusus schema QA, password dan APP_KEY QA acak; hanya file/proses privat sementara, nilainya tidak dicetak |

Tidak ada image/dependency diunduh. Tidak mengubah konfigurasi Docker/MCP atau container existing. Development `localhost:8140` tetap memakai container dan database semula.

## 3. Bukti isolasi sebelum mutation

Runner memeriksa label/container ID, jaringan internal, daftar mount, tmpfs, tidak ada published port, serta tidak adanya mount volume operasional. Kedua container hanya terhubung ke jaringan QA baru. Salinan source tidak mempunyai `.env` atau file SQLite development.

Sebelum membuat schema/fixture, koneksi **ke container baru** memverifikasi UUID/version server dan bahwa schema awal hanya schema bawaan MySQL. Baru sesudah itu schema QA dan user terbatas dibuat. Tidak mengambil UUID melalui koneksi ke MySQL operasional; isolasi dibuktikan melalui container baru, jaringan terpisah, tmpfs dan server baru tersebut.

Sebelum migration dan setiap pembuatan aplikasi test, helper existing `IsolatedMysql::verify()` memverifikasi environment testing, root `/qa` tanpa dotenv, manifest/run/hostname, DB host/schema/user, UUID server, versi 8.4 dan marker server-side. Config test hanya berlaku pada container/proses QA; tidak menggunakan `.env` development atau koneksi fallback operasional.

Tes produksi memulai aplikasi dengan guard QA pada environment testing, kemudian mengganti environment **hanya dalam proses tes** untuk menjalankan policy production sesungguhnya. HTTP selalu fake dan stray requests dicegah; jaringan internal memberi lapisan isolasi tambahan. Tidak ada bypass production baru ditambahkan pada source aplikasi.

Bukti lokal ignored: `qa-artifacts/mysql-hosting/isolation.json`, `state.json`, `migrate.log`, `schema.json`, log pengujian, dan `integrity.json`. Artefak/runner/source staging QA bukan paket release dan jangan di-stage bersama source aplikasi.

## 4. Migration dan schema aktual

**PASS:** seluruh **10 migration** working tree berhasil dari schema aplikasi kosong. Ini mencakup migration descriptive analysis, normalisasi username dan budget Groq terbaru.

**PASS:** sebelum fixture, seluruh 10 migration di-rollback pada schema QA disposable, lalu dijalankan ulang. Helper mensyaratkan tidak ada user/analisis/attempt budget sebelum cycle tersebut. Tidak ada rollback pada database development/operasional.

**PASS:** rollback descriptive analysis yang berisi skor/kategori null ditolak sesuai guard, tanpa menghapus hasil. Keberhasilan rollback schema kosong tidak berarti rollback data berisi analisis deskriptif aman dipaksakan.

Metadata aktual menunjukkan **27 tabel total**: 25 tabel aplikasi/framework, tabel migrations dan marker QA. Ada 28 entri kolom foreign key dan 75 index berbeda (94 entri kolom index). Angka ini hasil metadata server, bukan perkiraan laporan awal.

Yang diverifikasi dengan assertion atau skenario existing:

- Tabel akademik, history KRS dan dua tabel budget tersedia; seluruh migration tercatat selesai.
- `analisis_risiko.skor/kategori` nullable, `ringkasan` bertipe MySQL JSON, `versi_model` varchar(50).
- Parent/child recommendation FK ada dan insert orphan ditolak dengan MySQL error 1452.
- Unique username ada; update langsung ke username duplikat yang berbeda kapitalisasi ditolak dengan error 1062 pada collation target.
- Unique history/version dan proteksi relasi KRS tercakup workflow existing; duplikasi mutex budget ID 1 ditolak.
- CHECK level role ada dan nilai level 9 benar-benar ditolak dengan error 3819.
- Index mahasiswa/waktu analisis ada; default budget global ID 1 tersedia, rekomendasi memakai default/status yang dapat disimpan.

DDL/schema inventory tersimpan tanpa data akun pada `schema.json`. Pengujian mencakup constraint penting, bukan klaim bahwa setiap kombinasi semua constraint telah difuzz.

## 5. Authentication dan authorization

**PASS pada MySQL:** login/logout seluruh empat role, redirect dashboard sesuai role, session logout menjadi guest, serta penolakan akses dashboard lintas role. Seluruh route Owner yang ditemukan hanya GET/HEAD.

Suite existing dan tes Groq juga memeriksa kepemilikan data mahasiswa, penugasan Dosen, akses Admin, dan penolakan POST AI dari Dosen/Admin/Owner. Monitoring Owner tetap read-only; tidak ada hak tulis atau perubahan role internal.

Tes form production-demo tambahan menjalankan middleware production dengan token CSRF valid; token salah benar-benar menghasilkan **419**. Consent yang hilang ditolak sebelum provider. Ini bukan bukti konfigurasi HTTPS/cookie/proxy hosting nyata, tetapi membuktikan jalur request aplikasi pada MySQL QA.

## 6. Workflow akademik

**PASS sesuai cakupan suite MySQL:**

| Area | Cakupan yang dijalankan |
| --- | --- |
| Admin Akademik | CRUD master Program Studi, Semester, Mata Kuliah, Kelas Kuliah, Dosen dan Mahasiswa; validasi unik/relasi; pencarian dan filter; proteksi penghapusan; single-active semester |
| Mahasiswa | Pilihan/pengajuan KRS, kapasitas, penolakan/revisi/persetujuan, history/snapshot, semester tertutup, kepemilikan data, nilai final dan IPS/IPK |
| Dosen | Katalog/penugasan kelas, roster resmi, pembacaan kehadiran, validasi input nilai, revision lock, finalisasi dan larangan mengubah nilai final |
| Pimpinan Yayasan | Dashboard/agregat, filter program/semester, monitoring KRS/nilai/kehadiran, risiko dan rekomendasi tersimpan, laporan program serta pembatasan read-only |

Shared scenario traits existing dijalankan melalui `MysqlTestCase`, bukan dengan melemahkan guard SQLite pada `Tests\TestCase`. Tidak semua test Feature SQLite dipaksakan ke MySQL. Cakupan kehadiran mengikuti kemampuan aplikasi yang ada, termasuk pembacaan/rekap; tidak menambahkan fitur input kehadiran baru.

## 7. Groq production-demo — HTTP fake saja

**PASS pada MySQL nyata, tanpa transport Groq nyata:**

- Default production-demo OFF dan konfigurasi tidak lengkap ditolak; allowlist user/database/environment diperiksa; local WSL yang tidak cocok tetap ditolak.
- Policy production menerima koneksi MySQL QA aktual dengan `SELECT DATABASE()`, bukan identitas koneksi yang dimock.
- Readiness GET sesuai konfigurasi dan tidak memanggil provider. Form production menerima consent/CSRF yang benar, menyimpan hasil, lalu redirect; tanpa consent atau dengan CSRF salah tidak memanggil provider.
- Mock existing dan hasil yang menyimulasikan production-live mempunyai provenance berbeda; lookup JSON tidak memakai mock sebagai pengganti live.
- Hasil valid tersimpan sebagai parent dan rekomendasi, skor/kategori null. Histori lama tetap identik pada fixture tes.
- Reuse hasil matching dan navigasi Mahasiswa/Owner tidak menambah fake request atau attempt. Mahasiswa lain tidak melihat hasil milik akun utama fixture.
- Provider 401/403/429/500/503, timeout dan respons invalid ditolak dengan pesan aman; attempt persisten tetap dikonsumsi, lease dilepas, tidak ada hasil parsial atau retry otomatis. Retry eksplisit yang diizinkan setelah window kemudian dapat menyimpan hasil valid.
- Respons provider valid diikuti SQL insert child gagal: transaksi MySQL rollback, hasil lama tetap utuh, kuota tetap dikonsumsi.
- Lifetime quota user/global, window dua/menit, cache clear, lease kedaluwarsa dan token pemilik lama diverifikasi pada MySQL.

Label transport `live` pada fixture **hanya menguji cabang production dengan respons fake**. Tidak ada hasil tersebut yang berasal dari Groq sungguhan atau disimpan ke database development. Datadir QA dibuang saat cleanup.

Tidak menguji availability/model/kuota provider, koneksi keluar hosting atau billing. Live flag hanya diganti dalam memori proses QA yang memakai fake; `.env` dan konfigurasi production sebenarnya tidak diaktifkan.

## 8. Transaksi, locking dan concurrency nyata

Pengujian menggunakan dua proses PHP dan connection ID MySQL berbeda, dimulai dari barrier bersama; bukan dua panggilan berurutan.

**Dua race Groq:**

| Skenario | Hasil putaran regresi gabungan |
| --- | --- |
| Double-submit mahasiswa/semester sama | Connection **206/207**: satu `new`, satu `reused`, analysis ID sama, total **1 HTTP fake**, attempt global 1 |
| Dua user berebut global limit 1 | Connection **209/210**: satu `new`, satu `denied`, total **1 HTTP fake**, attempt global 1 |

Statement `FOR UPDATE` teramati pada reservasi/validasi lease. Cache lock per mahasiswa memakai store database bersama pada race; global mutex budget menggunakan row lock InnoDB. Lease akhir dilepas dan tidak ada dua analisis baru pada kasus yang diuji.

**Sembilan race akademik existing juga lulus:** aktivasi semester, penurunan kapasitas vs submit, dua submission, dua mahasiswa pada slot terakhir, keputusan KRS bersamaan, pembuatan nilai bersamaan, finalisasi vs edit, edit stale revision, dan penghapusan kelas yang berelasi.

Metrik `Innodb_deadlocks` pada logger existing bernilai null/tidak tersedia, sehingga **tidak mengklaim counter deadlock nol**. Tidak melakukan fault injection deadlock arbitrer atau load test besar. Race yang dijalankan selesai dengan outcome yang diperiksa, bukan exception deadlock tak tertangani. Source budget tidak mengulang provider otomatis; gangguan transaksi tetap harus fail closed. Perilaku semua pola deadlock/topologi multinode masih belum dibuktikan.

## 9. Versi dan perbedaan engine

Metadata server QA:

- MySQL **8.4.11**, InnoDB pada tabel yang diinventarisasi.
- Transaction isolation **REPEATABLE-READ**.
- Collation database **utf8mb4_unicode_ci**.
- Session timezone **SYSTEM**, system timezone **UTC**.
- SQL mode mencakup `ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, `NO_ZERO_IN_DATE`, `NO_ZERO_DATE`, `ERROR_FOR_DIVISION_BY_ZERO`, `NO_ENGINE_SUBSTITUTION`.

JSON path, casts, agregat, nullability, constraint dan transaksi yang diuji bekerja pada setting tersebut. KRS menggunakan wall time WIB sesuai service existing; nilai/waktu history dibaca pada workflow tes. Tidak mengubah timezone/config aplikasi agar tes lulus.

Tidak ada query SQLite `PRAGMA` yang dijalankan pada jalur production-demo MySQL; guard local tetap merupakan jalur SQLite tersendiri. Penamaan tabel oleh `Schema::getTableListing()` default Laravel menyertakan schema pada MySQL, sehingga test metadata baru menggunakan `schemaQualified: false`. Ini koreksi test metadata, bukan perubahan nama tabel aplikasi.

**MariaDB NOT VERIFIED.** Tidak ada image MariaDB cached yang digunakan dan tidak mengunduh image baru. Native driver MariaDB, sintaks CHECK/DROP CHECK, representasi JSON serta engine/versi hosting lain masih perlu diuji bila menjadi target. MySQL 8.0/versi lain juga tidak otomatis tersertifikasi oleh hasil 8.4.11.

## 10. Masalah yang ditemukan dan perubahan

Tidak ada perubahan pada business logic, migration aplikasi, HTTP client, `.env`, APP_KEY, guard WSL atau konfigurasi operasional untuk membuat MySQL lulus.

Masalah persiapan/test yang diperbaiki:

1. Salinan QA awal belum menyertakan `database/factories`; percobaan pertama gagal memuat UserFactory. Harness diperbaiki untuk menyalin factory source, tanpa menyalin database/credential. Hasil percobaan tersebut tidak dianggap PASS.
2. Assertion tabel metadata baru mengharapkan nama tanpa prefix, sedangkan API Laravel mengembalikan nama schema-qualified. Test menggunakan parameter API yang sesuai; tidak mengubah schema/constraint.
3. Pint menemukan style pada dua file test baru; diperbaiki dan pemeriksaan ulang lulus.

Source test/support baru yang perlu direview:

- `tests/Mysql/GroqHostingTest.php`: tujuh tes policy/transport fake, JSON, histori, kuota, kegagalan dan form production.
- `tests/Mysql/GroqConcurrencyTest.php`: dua race Groq lintas proses/koneksi.
- `tests/Mysql/HostingSchemaTest.php`: tiga tes metadata/constraint dan guard rollback deskriptif.
- `tests/Mysql/HostingAuthenticationTest.php`: login/logout empat role dan akses lintas role.
- `tests/Support/mysql-groq-race-worker.php`: worker barrier yang terlebih dahulu melewati guard MySQL QA.
- `tests/Support/mysql-hosting-migration-cycle.php`: cycle migration khusus schema QA kosong dengan precondition.
- `tests/Support/mysql-hosting-schema-review.php`: metadata schema dari koneksi QA terverifikasi.

Runner dan credentials tidak menjadi source aplikasi. Cleanup counter dalam concurrency test hanya memulihkan state yang dibuat tes pada server QA yang lolos guard, bukan API reset kuota production.

## 11. Hasil pengujian dan regression

| Pemeriksaan | Hasil aktual |
| --- | --- |
| Migration awal | **10/10 PASS** |
| Rollback seluruh migration pada QA kosong + remigration | **PASS**, 10 migration |
| Suite awal setelah factory harness diperbaiki | **62 tes / 1.095 assertions PASS** |
| Regresi gabungan setelah penambahan schema/auth/concurrency/window tests | **69 tes / 1.248 assertions PASS**, `regression-final.log` |
| Form production lengkap yang ditambahkan terakhir | **1 tes / 21 assertions PASS**, `production-form.log` |
| Pint | **7 file PHP baru PASS** |
| PHP syntax | **7 file PHP baru PASS** |
| `git diff --check` dan whitespace file baru | **PASS** |
| MariaDB | **NOT RUN / NOT VERIFIED** |
| Groq live / external hosting | **NOT RUN** |

Form production terakhir dijalankan terpisah setelah suite gabungan; **bukan klaim satu eksekusi berisi 70 tes**. Source aplikasi tidak berubah di antara kedua eksekusi. Test yang telah lulus tidak diulang tanpa perubahan/alasan; penambahan test dan perbaikan harness/metadata menjadi alasan regresi gabungan.

Tidak menjalankan ulang build/browser QA karena tidak ada perubahan frontend/source produk. Bukti SQLite sebelumnya tetap terpisah dan tidak dihitung sebagai MySQL PASS.

## 12. Integritas dan cleanup

Baseline opaque diambil sebelum run untuk `.env`, SQLite development beserta sidecar yang ada, manifest runtime dan ledger live pertama. Sesudah pengujian dan cleanup: **4/4 file byte-identical**, himpunan file terlindungi tetap sama, dan **Git HEAD tidak berubah**. Ringkasan tanpa nilai/hash rahasia berada di `integrity.json`.

Dengan byte database development identik, profil Hendrik/Jovian, akun, catatan akademik dan histori Groq dalam database tersebut tidak berubah. Tidak ada query ke MySQL operasional, sehingga **perbandingan seluruh isi database operasional sebelum/sesudah NOT VERIFIED**; jangan mengubah pernyataan “tidak diakses” menjadi klaim isi server telah difingerprint. Container operasional tetap aktif pada pemeriksaan akhir.

Dua container QA dan jaringan internal run ini telah dihapus setelah label kepemilikan diverifikasi. Datadir tmpfs hilang bersama container. File auth/password/APP_KEY QA sementara juga dihapus. Container development/operasional dan resource Servisa tidak dihentikan atau diubah. Source snapshot terpilih di `/tmp` dan bukti lokal ignored dipertahankan; jangan diunggah sebagai release.

Tidak ada Git add/commit/push/merge/reset/clean, sudo, deployment, download atau request Groq live. Perubahan source lokal sebelum pekerjaan tetap dipertahankan.

## 13. Batasan sebelum membeli/menjalankan hosting

**Sudah terbukti:** jalur akademik dan Groq mocked dalam cakupan di atas bekerja pada MySQL QA 8.4.11 dengan source terkini.

**Masih REQUIRED / NOT VERIFIED:**

1. Versi/driver DB hosting aktual; MariaDB jika dipilih harus direhearsal sendiri. Jangan menonaktifkan FK/CHECK/strict mode demi melewati error.
2. PHP web/CLI/extensions, document root `/public`, rewrite, permission storage/cache, HTTPS/cookie/proxy, config cache privat, dan backup/restore pada provider.
3. Dataset dan account initializer hosting yang tidak bergantung pada WSL, password aman, allowlist akun dan anggaran demo. Tidak memindahkan SQLite utama/credential/artefak QA.
4. Outbound HTTPS/CA/TLS ke Groq, dukungan model/parameter, timeout PHP/proxy di atas waktu HTTP 25 detik plus overhead, serta kapasitas worker hosting.
5. Load/concurrency lebih besar, gangguan jaringan/cache, failover/multinode dan seluruh pola deadlock; dua-worker race bukan load benchmark.
6. Persetujuan terpisah sebelum satu smoke live terbatas pada staging aman. Key hanya server-side; production-demo tetap default OFF sampai konfigurasi dan staging disetujui.

**Keputusan akhir: CONDITIONAL GO untuk persiapan shared hosting; bukti MySQL versi ini PASS, bukan GO deployment atau aktivasi Groq live publik.**
