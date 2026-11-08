# Verifikasi akhir sistem UniNalar

Laporan ini memisahkan fitur terdefinisi, bukti lokal aktual, keputusan akademik
yang belum tersedia, dan persyaratan rilis. Hasil sesi Mahasiswa/Dosen/Admin/Owner
sebelumnya merupakan baseline historis, bukan hasil final pada laporan ini.

## 1. Git dan workspace

PASS: workspace WSL `/home/hendra_huang/projects/UniNalar-main`, branch `main`,
HEAD `02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`, remote
`https://github.com/Liwaru/UniNalar.git`. Working tree memang sudah berisi seluruh
implementasi empat role yang belum di-commit. Semua dipertahankan; tidak ada pull,
checkout, reset, clean, commit, push atau deployment.

## 2. Perubahan lokal pada sesi final

Perbaikan terarah mencakup CSS bersama/akademik/login/landing untuk ukuran teks, kontras
dan pengurangan efek dekoratif; teks login dan landing agar sesuai kemampuan
aplikasi; tautan Ringkasan pada sidebar seluruh role; skip link pada shell bersama;
referensi error profil mahasiswa; keterbacaan tabel dan keputusan KRS mobile;
komponen pagination bahasa Indonesia dan
registrasinya pada AppServiceProvider. Tidak ada perubahan aturan akademik,
model, migration atau service penulisan KRS/nilai.

Tes landing diperkuat agar tidak mempromosikan analisis otomatis yang belum
diaktifkan. Ekspektasi navigasi mahasiswa kini mensyaratkan tepat satu tautan
aktif juga pada dashboard. Ditambahkan regresi proteksi file privat dan metadata
authorization seluruh endpoint akademik. Assertion pagination ditambah teks
Indonesia; assertion integritas dan tes existing tidak dihapus/dilemahkan.

Inventaris working tree akhir tersedia lokal pada
`qa-artifacts/final/working-tree.txt`; perubahan sebelum sesi final tidak
diatribusikan sebagai hasil sesi ini.

## 3. Daftar seluruh halaman dan route aktual

Inventaris `artisan route:list --json` menghasilkan **96 route**, terdiri dari
**84 endpoint akademik** dan 12 route publik, autentikasi, redirect, area role,
health serta storage framework. Endpoint akademik memuat **58 halaman GET**;
landing dan login menambah dua halaman HTML publik. Route parameter dipenuhi
dari fixture sintetis; tidak diuji hanya dari daftar kebutuhan.

| Role / prefix URL | Halaman GET yang tersedia | Mutasi terdefinisi |
| --- | --- | --- |
| Mahasiswa `/dashboard/mahasiswa` | Dashboard; profil; KRS beserta katalog kelas dan riwayat; nilai beserta IPS/IPK; kehadiran; risiko beserta faktor; rekomendasi; tindak lanjut (8 halaman) | Kontak profil, tambah/hapus pilihan kelas, ajukan KRS |
| Dosen `/dashboard/dosen` | Dashboard; kelas; detail kelas; peserta resmi; kehadiran kelas; profil; nilai (7 halaman) | Kontak profil, simpan/edit draft nilai, finalisasi |
| Admin `/dashboard/admin-akademik` | Dashboard; list/create/detail/edit masing-masing enam master: program, semester, mata kuliah, dosen, mahasiswa, kelas; review dan detail pengajuan KRS (27 halaman) | CRUD enam master; setujui/tolak versi KRS terbaru |
| Owner `/dashboard/owner` | Dashboard; mahasiswa/detail; dosen/detail kelas diampu; kelas/detail peserta; KRS/detail riwayat; nilai final; kehadiran; risiko/detail faktor; rekomendasi; tindak lanjut; laporan (16 halaman) | Tidak ada mutasi Owner |
| Publik | `/`, `/login` | Login; logout hanya dengan auth dan CSRF |

Route tambahan: `/dashboard` redirect sesuai role; `/area/*` JSON dengan daftar
role existing; `/up` health; storage GET/PUT menggunakan capability URL bertanda
tangan framework, bukan endpoint akademik tanpa otorisasi.

## 4. Status fitur

| Fitur | Status dan batas |
| --- | --- |
| Login/logout, role dan session | PASS pada regresi dan HTTP/browser yang dijalankan |
| Master akademik dan penugasan kelas | PASS; tidak mencakup lifecycle pembuatan akun baru |
| KRS, versi, review dan keputusan | PASS; mahasiswa memilih, Admin memutuskan, Owner membaca |
| Roster resmi Dosen/Owner | PASS; pending/ditolak/dibatalkan tidak dianggap resmi |
| Draft, edit, finalisasi dan kunci nilai | PASS; pembukaan kembali nilai final NEEDS SPECIFICATION |
| IPS/IPK dan SKS | PASS menurut aturan terdokumentasi; tidak membuat konversi angka→huruf baru |
| Kehadiran | PASS untuk data tersimpan; input/koreksi NEEDS SPECIFICATION |
| Risiko, faktor dan rekomendasi | PASS untuk hasil tersimpan dan scope; engine/pipeline AI belum diimplementasikan |
| Tindak lanjut | PASS untuk monitoring tersimpan; transisi/mutasi NEEDS SPECIFICATION |
| Laporan Owner | PASS untuk laporan baca-saja; ekspor sensitif NEEDS SPECIFICATION |
| Browser/responsif | Hasil run final dicatat pada bagian 20–22 |
| Produksi/shared hosting | PARTIAL / NOT VERIFIED pada paket hosting nyata |

## 5. Halaman baru

Tidak ditambahkan halaman akademik baru atau duplikat pada sesi final ini.
Workflow terdefinisi sudah memiliki halaman. Ditambahkan satu komponen pagination
reusable, bukan modul spekulatif atau endpoint baru.

## 6. Temuan

FIXED: ukuran metadata/label terlalu kecil; warna bantuan kurang terbaca; shadow,
gradient dan efek dekoratif berlebihan pada publik/login; sidebar tiga role belum
memiliki tautan Ringkasan eksplisit; referensi aria-describedby profil mengarah
ke elemen error yang belum ada; pagination memakai bahasa Inggris; teks publik
menyiratkan scoring/AI otomatis sudah aktif.

Diagnosis browser: tool MCP tidak terekspos di sesi ini; konfigurasi WSL tidak
memiliki `mcp_servers`, sedangkan Windows memiliki Playwright dan Chrome DevTools
dengan launcher Windows. Tidak ditemukan log startup browser MCP yang membuktikan
server tersebut berjalan di extension host WSL. Log analytics/plugin-sync yang
gagal bukan bukti browser MCP sedang aktif. Browser Linux lokal tidak ditemukan.
Chrome Windows dan cache Playwright Windows ternyata sudah tersedia.

## 7. Perbaikan dan retest

Tipografi kecil diseragamkan minimal 11 px pada komponen bersama, teks tabel
12 px, input sentuh 16 px. Warna bantuan digelapkan tanpa mengubah palet identitas.
Efek dekoratif publik/login dikurangi; posisi komponen dan identitas UniNalar
dipertahankan. Teks kemampuan aplikasi kini menyebut hasil analisis tersimpan
dan belum aktifnya analisis otomatis. Ilustrasi tetap diberi label simulasi.

Pada tabel review KRS mobile, kolom identitas diberi ruang baca dan NIM tidak
dipatahkan per beberapa karakter. Form keputusan panjang ditampilkan pada detail
di layar kecil; tabel tetap memperlihatkan status dan tautan detail. Desktop
mempertahankan aksi review cepat.

Pagination memakai satu komponen dengan Sebelumnya/Berikutnya, posisi halaman,
jumlah catatan dan query string asli. Skip link tersedia untuk semua role.
Selama perbaikan visual, penghapusan transform dekoratif sempat ikut memengaruhi
posisi skip link landing; screenshot mendeteksinya dan posisi tersembunyi sampai
fokus keyboard dipulihkan. Shadow utility pada ilustrasi juga dihapus.

Kegagalan awal lainnya merupakan harness/ekspektasi: selector browser memilih
dua varian tautan Next responsive; selector dibatasi ke elemen terlihat. Ekspektasi
navigasi dashboard yang tadinya nol tautan aktif diperbarui menjadi satu setelah
menu Ringkasan ditambahkan. Router QA eksplisit dipakai agar pemeriksaan path
file privat benar-benar melewati front controller, bukan sekadar 404 server PHP.

## 8. Workflow antar-role

PASS pada HTTP dan regresi: Admin CRUD/penugasan → Dosen melihat kelas →
Mahasiswa memilih dan mengajukan KRS → Admin menolak → pengajuan versi baru →
Admin menyetujui → Dosen melihat roster resmi → input/edit/finalisasi nilai →
penolakan edit setelah final → Mahasiswa dan Owner membaca nilai/IPK konsisten.

Data risiko/rekomendasi/tindak lanjut dan kehadiran di QA adalah fixture sintetis
yang ditandai, bukan skor baru produksi atau hasil engine AI. Scope mahasiswa,
provenance demo, relasi faktor, dan catatan internal diuji. Kehadiran tidak diberi
workflow input baru. Nilai historis dan peserta resmi tanpa nilai dipakai untuk
mengamati chart dan form penilaian browser dalam keadaan nyata.

## 9. Navigasi

Seluruh menu sidebar aktual diikuti dalam browser. Dashboard memiliki tepat satu
tautan aktif. Breadcrumb, brand ke dashboard sesuai role, tautan detail, filter,
reset dan pagination diperiksa. Menu mobile landing dibuka lalu ditutup setelah
navigasi bagian; dialog bantuan login dan Escape diuji. Skip link dan scroll
tabel diuji melalui keyboard. Ini bukan sertifikasi aksesibilitas menyeluruh.

## 10. Form dan CRUD

HTTP menguji CRUD enam master, duplikasi, relasi yang mencegah hapus, penugasan
kelas, kontak mahasiswa/dosen, KRS dua versi dan penilaian. Semua memakai cookie
dan CSRF aktif. Browser menambah/edit/menghapus program sintetis serta membaca
validasi duplikasi; mengisi/edit/finalisasi nilai peserta resmi sintetis; memakai
filter/pagination; menguji password toggle, bantuan login, fokus profil dan logout.
State create/edit/detail/list, data kosong dan pesan validasi direkam.

## 11. Authentication dan authorization

PASS untuk kasus yang diuji. Regresi meliputi username/password, remember token,
throttle, intended URL role, regenerasi session dan logout. HTTP memeriksa CSRF
419, direct URL lintas role, serta Owner yang ditolak pada mutasi Admin/Dosen.
Tes metadata memeriksa auth dan level yang tepat pada seluruh 84 endpoint akademik,
dan memastikan semua endpoint Owner hanya GET/HEAD. Produksi harus memakai HTTPS,
cookie secure/HttpOnly dan konfigurasi reverse proxy yang benar.

## 12. IDOR, file access dan privacy

Regresi existing tetap mencakup mahasiswa lain, kelas/dosen lain, query scope,
stored XSS, pencarian menyerupai SQL, mass assignment profil, keputusan KRS lama,
revisi nilai kedaluwarsa dan nilai final. Owner tidak menampilkan kontak pribadi,
password, JSON internal atau catatan tindak lanjut internal.

HTTP final memeriksa `.env`, Git, Composer, vendor, tests, QA artifacts dan log
privat tidak bisa diunduh; upload local tanpa signature ditolak. Tes tambahan
memastikan file local privat tidak dapat dibaca atau ditulis dengan URL unsigned.
Route storage framework memang tidak memakai login, tetapi memverifikasi signature
dan jenis capability; tidak diperlakukan sebagai endpoint upload publik bebas.

## 13. Identitas database dan server QA

SQLite PHPUnit diverifikasi `APP_ENV=testing` dan `:memory:` sebelum write.
Fixture HTTP/browser memakai file `/tmp/uninalar-qa-<hex>.sqlite` baru/kosong;
identitas file konfigurasi dan `PRAGMA database_list` harus cocok sebelum migration.
Setiap fixture tambahan kembali memverifikasi file aktual sebelum write.

MySQL memakai container baru, tmpfs `/var/lib/mysql`, network internal, tanpa
published port/volume data persisten. Manifest diperiksa terhadap UUID/version
server aktual, hostname PHP, schema unik `qa_`, network/mount dan marker server
sebelum write. Database `uninalar` tidak ada pada instance QA baru itu. Tidak
ada koneksi, salinan, migration/seeder atau data pribadi database operasional.

Server browser berada pada network QA internal dengan mount workspace dan tanpa
published port. Windows mengakses relay sementara pada IP WSL; relay hanya menerima
peer gateway Windows yang terdeteksi, lalu meneruskan HTTP melalui socket lokal
container. Tidak ada firewall, portproxy atau konfigurasi jaringan permanen diubah.

## 14. Integritas data

FK/unique constraints, transaksi dan row locking tetap melalui implementasi
existing. Regresi memeriksa proteksi hapus berelasi, kapasitas reservasi, identitas
kelas setelah dipakai, SKS historis, snapshot pengajuan immutable, dan parent/history
KRS konsisten. Nilai draft tidak dipublikasikan sebagai final dan final tidak bisa
diubah melalui workflow terdefinisi. Tidak ada perubahan schema pada sesi ini.

## 15. Concurrency

PASS: sembilan skenario MySQL dua proses/koneksi. Aktivasi semester bersaing;
kapasitas melawan submit; submit ganda; kursi terakhir; keputusan bersaing;
pembuatan nilai ganda; finalisasi melawan edit; stale revision; hapus kelas
berelasi. Hasil mencatat penguncian dan koneksi berbeda. Field deadlock status
yang tidak tersedia bukan bukti nol deadlock. Ini bukan jaminan seluruh interleaving
atau beban produksi telah diuji.

## 16. SQLite

PASS: **121 tes / 2.110 assertions** pada run final SQLite `:memory:`.
Suite mencakup regresi empat role, tests keamanan route/file baru, navigasi aktif,
serta pagination bahasa Indonesia. Tidak ada kegagalan yang disembunyikan.

## 17. MySQL

PASS: **57 tes / 1.003 assertions**, termasuk sembilan skenario konkurensi,
berdasarkan `qa-artifacts/final/mysql-tests.log`. Source akademik/DB tidak berubah
setelah run ini; perubahan terakhir berikutnya hanya CSS/markup publik dan tampilan review KRS;
service akademik dan query database tetap sama.

## 18. HTTP

PASS: **317 pemeriksaan** pada source final, CSRF aktif, cookie/session nyata,
database sintetis terisolasi dan router QA eksplisit. Bukti mencakup workflow
empat role, persistensi akhir, authorization, private-file denial dan seluruh asset
manifest HTTP 200. APP_ENV local hanya pada proses server QA agar CSRF aktif,
bukan perubahan environment operasional.

## 19. PHP, Pint, Vite

PHP 8.2.34 lokal Docker; PHP host tidak tersedia. Syntax PASS untuk **117 file PHP**, Pint test mode PASS **118 file source**.
Arsip QA tidak masuk hitungan. Vite PASS **127 transformed modules**, dibangun
kembali dari dependency existing. Build terakhir setelah retest publik dicatat
pada log lokal. Warning `@charset`
diverifikasi melalui log dan CSS hasil build; tidak sekadar mengutip laporan Owner.
Lockfile Composer/npm tetap sama; tidak ada instalasi atau upgrade dependency.
Setelah koreksi markup terakhir, tes terarah Admin/KRS **30 tes / 322 assertions**
dan landing **2 tes / 30 assertions** juga lulus. Build final tidak mencatat
warning charset; semua 10 entry manifest tersedia dan CSS tidak memuat charset
import. Ukuran: CSS dashboard 281,69 kB (gzip 42,17), akademik 6,13 kB
(gzip 1,74), landing 289,76 kB (gzip 44,28).

## 20. Browser QA

Tool MCP tetap tidak terekspos, tetapi hambatan browser **berhasil diatasi tanpa
instalasi/perubahan konfigurasi permanen**. Driver Playwright yang sudah tersimpan
di cache Windows menjalankan Chrome Windows **156.0.8078.81** headless dengan profil sementara; driver existing
**Playwright 1.65.0-alpha-2026-10-30**. Driver tersebut hanya tooling QA lokal,
bukan dependency aplikasi atau komponen yang akan dirilis.
Probe membuka halaman login QA melalui relay yang dibatasi host Windows.

PASS: run matriks memuat **65 kasus halaman/state pada lima viewport (325 capture)**,
mencakup seluruh 60 halaman HTML aktual plus empty/filter/pagination dan form
nilai. Pemeriksaan DOM mencakup HTTP status, overflow halaman, ID duplikat, label,
gambar rusak, referensi ARIA dan kontras pada label/tabel/teks bantuan yang disampel.
Console, pageerror, request gagal dan HTTP error dicatat, termasuk domain eksternal.

Matriks awal adalah bukti diagnosis; run final dan retest publik setelah perbaikan
skip link merupakan bukti penerimaan. Run penuh lulus **55 interaction checks**, tanpa console/pageerror, request
gagal, atau HTTP error browser. Retest publik/KRS terakhir lulus **20 capture dan 15 interaction checks**;
termasuk skip link fokus keyboard, penolakan KRS → pengajuan ulang → persetujuan,
roster resmi Dosen dan status baca-saja Owner. Hasil digabung berdasarkan nama
kasus, sehingga tetap **325 capture unik**, bukan dihitung sebagai halaman baru. Tidak ada klaim pengujian browser mobile
fisik, Safari/Firefox, pembaca layar atau full WCAG.

Diagnosis konfigurasi mengikuti skill OpenAI Docs dan
[dokumentasi resmi konfigurasi MCP](https://developers.openai.com/learn/docs-mcp).
Konfigurasi Windows/WSL dibaca hanya pada field MCP, tanpa menampilkan kredensial.
Tidak ada MCP ditambahkan ke config global. Log analytics yang gagal dipisahkan
dari bukti koneksi browser yang benar-benar diuji.

## 21. Screenshot

Bukti lokal berada pada `qa-artifacts/final/screenshots/` (ignored Git). Nama
memuat role, jenis halaman dan lebar viewport. Screenshot full-page mencakup
list/detail/create/edit, dashboard, empty state, pagination, login dan validasi;
capture viewport tambahan membantu pemeriksaan keterbacaan tanpa mengecilkan
seluruh halaman panjang. Screenshot yang dipilih diperiksa secara visual;
seluruh matriks mendapat pemeriksaan rendering/DOM otomatis. Tidak diklaim
setiap pixel 325 screenshot diperiksa manual satu per satu.

Contoh bukti yang dapat dibuka di workspace:

- [Login mobile](../qa-artifacts/final/screenshots/public-login-375.png)
- [Landing desktop](../qa-artifacts/final/screenshots/public-landing-1366-viewport.png)
- [Admin mobile](../qa-artifacts/final/screenshots/admin-dashboard-375-viewport.png)
- [Mahasiswa mobile](../qa-artifacts/final/screenshots/mahasiswa-dashboard-375-viewport.png)
- [Dosen desktop](../qa-artifacts/final/screenshots/dosen-dashboard-1366-viewport.png)
- [Owner desktop](../qa-artifacts/final/screenshots/owner-dashboard-1366-viewport.png)
- [Validasi Admin](../qa-artifacts/final/screenshots/admin-validation-390.png)

## 22. Responsive results

Viewport wajib: 375×812, 390×844, 768×1024, 1366×768 dan 1920×1080. Run penuh: **65 capture PASS pada masing-masing viewport**; semua halaman
memiliki status 200, tanpa overflow dokumen, ID duplikat, label hilang, gambar
rusak atau referensi ARIA putus. Sampel kontras label/tabel/bantuan tidak
menghasilkan temuan di bawah rasio 4,5. Retest terakhir menimpa bukti kasus
terdampak, tanpa menganggapnya halaman baru. Tabel akademik lebar tetap
boleh digulir dalam region, sementara halaman tidak boleh overflow horizontal.
Form yang berada di sisi kanan tabel scroll bukan dianggap overflow dokumen.
Pemeriksaan kontras terbatas pada elemen yang diperiksa; bukan sertifikasi AA.

## 23. Natural UI/UX

Identitas logo, font, palet dan shell dipertahankan. Layout tidak dirombak total.
Komponen akademik dipakai ulang, kontrol punya fungsi nyata, label/pesan ringkas,
warna menunjukkan status, dan pagination konsisten lintas role. Teks bantuan
yang sebelumnya 8–10 px diperbesar; warna muted yang terlalu terang digelapkan.
Efek shadow/gradient/glass berlebihan dikurangi pada landing/login. Data ilustrasi
publik tetap diberi label simulasi; tidak menjadi statistik dashboard aktual.

## 24. Performance

Query-scale regressions existing membandingkan satu dan 20 baris mahasiswa/roster;
eager loading, aggregate count, filtering dan pagination dipertahankan. Tidak ada
cache statistik akademik atau optimasi tanpa bukti baru. Manifest 10 entry
diverifikasi terhadap file aktual; ukuran asset dicatat pada build log.

Waktu browser mencakup relay lintas Windows/WSL, network-idle wait dan screenshot,
sehingga bukan latency aplikasi murni. Peak memory dashboard GPA pada skala
produksi, sustained load dan hosting resource limits tetap NOT VERIFIED. Tidak
ada klaim peningkatan kinerja produksi atau kapasitas pengguna tertentu.

## 25. Risk/AI readiness

PASS: menampilkan hasil/faktor/rekomendasi tersimpan dengan scope dan provenance
yang tersedia; demo/simulasi yang diketahui dikecualikan. Tidak ada inferensi,
formula, bobot, threshold, pipeline AI atau rekomendasi otomatis baru. Provenance
kosong tidak membuktikan validitas hasil. Pipeline/ingestion/audit metode AI
belum diimplementasikan dan memerlukan spesifikasi resmi.

## 26. Academic policy blockers

NEEDS SPECIFICATION: metode risiko dan validasi model, aturan data kurang,
otorisasi hasil AI, input/koreksi kehadiran, transisi tindak lanjut, pembukaan
nilai final, konversi angka→huruf, koreksi SKS historis, pemetaan Kaprodi ke
program tertentu, kebijakan catatan internal dan ekspor sensitif. Tidak ada
kebijakan akademik baru dibuat untuk memperoleh status selesai.

## 27. Shared hosting readiness

PARTIAL: source dapat disiapkan untuk shared hosting tanpa Docker/WSL, tetapi
provider/paket belum diberikan atau diuji. Persyaratan diturunkan dari lockfile:
PHP ^8.2 beserta batas package aktual, Composer 2, PDO/pdo_mysql dan ekstensi
runtime, MySQL/JSON/FK/index yang sesuai, root `public/`, izin storage/cache,
driver session/cache/queue, HTTPS, secrets, logging, backup dan restore.
MySQL 8.4 diuji; MariaDB paket hosting nyata NOT VERIFIED.

Lihat [checklist hosting praktis](shared-hosting-readiness.md). Tidak ada Composer,
Node, Docker atau WSL yang dianggap harus tersedia di hosting jika artifact rilis
dibangun di lingkungan build yang sesuai. Runtime target tetap perlu diperiksa.

## 28. Deployment checklist

1. Identifikasi provider/paket; verifikasi PHP web/CLI, ekstensi, DB dan batas sumber daya.
2. Siapkan release allowlist: source direview, vendor production sesuai lockfile,
   `public/build` lengkap; tanpa tests, Git, QA, browser cache, `.env` lokal, logs atau `public/hot`.
3. Tinjau Composer scripts dan instalasi production secara terpisah; jangan menjalankan `composer setup`.
4. Tetapkan root `public/`, rewrite, ownership storage/bootstrap-cache dan proteksi file privat.
5. Provision secret/APP_KEY yang benar, production/debug=false, HTTPS dan secure cookies.
6. Verifikasi session/cache/queue serta SMTP/cron hanya untuk workflow yang benar-benar digunakan.
7. Rehearsal backup/restore dan migration pada salinan yang disetujui; verifikasi identitas DB sebelum migration operasional yang disetujui terpisah.
8. Uji staging empat role, assets, KRS/nilai/history, responsive dan private-file denial.
9. Siapkan rollback source bersama rekonsiliasi data; minta approval deployment konkret.

Checklist ini tidak mengeksekusi deployment, migration atau konfigurasi permanen.

## 29. Remaining issues

NOT VERIFIED: provider hosting, staging/produksi nyata, TLS/proxy configuration,
restore data nyata, load/peak memory produksi, pentest menyeluruh, mobile fisik,
browser selain Chrome, pembaca layar, dan audit aksesibilitas lengkap. Workflow
yang belum dispesifikasikan tetap seperti bagian 26; tidak dipresentasikan
sebagai fitur yang sudah diimplementasikan.

Browser lokal yang berhasil tidak memperbaiki konfigurasi MCP WSL secara permanen.
Jika integrasi MCP diinginkan untuk sesi berikutnya, pengguna perlu menyetujui
konfigurasi khusus WSL serta meninjau package/launcher; tidak cukup menyalin
`npx.cmd` Windows tanpa memeriksa runtime. Instalasi baru memerlukan izin spesifik.

## 30. Final verdict

**PASS — fitur terdefinisi empat role, regresi otomatis dan browser Chrome lokal
pada lima viewport yang diuji. PARTIAL — release/production readiness**, karena
paket hosting, staging produksi, restore nyata, load dan keputusan akademik yang
belum ditetapkan tetap merupakan gate terpisah. Hambatan visual browser lokal
sudah diatasi; konfigurasi MCP WSL permanen tidak diubah.

Ringkasan bukti: SQLite **121/2.110 assertions**, MySQL **57/1.003 assertions**
termasuk **9 race scenarios**, HTTP **317 checks**, browser **325 capture unik**,
**55 + 15 interaction checks** dan **20 focused retest captures**. Seluruh hasil
tersebut PASS; diagnosis/percobaan gagal sebelumnya tetap disimpan terpisah.

Tidak ada klaim 100% lengkap, full WCAG, pentest menyeluruh atau siap produksi.
Bukti aktual disimpan dalam satu direktori `qa-artifacts/final/`; laporan sesi
terdahulu dipertahankan sebagai snapshot. Container/network/relay QA dan profil
probe browser dibersihkan setelah pengujian. Container operasional tetap berjalan.
Tidak ada DB operasional diakses, konfigurasi permanen diubah, dependency baru
diinstal, commit/push atau deployment.
