# Autentikasi username dan fixture akademik development

> Path `/path/to/UniNalar-main`, `~`, `<windows-user-home>` dan `<runtime-tag>`
> adalah placeholder publik pengganti identitas mesin pada bukti lokal. Ganti sesuai
> workspace/runtime development yang sudah diverifikasi sebelum memakai contoh perintah.

Implementasi 8 November 2026 pada source WSL dan SQLite development terisolasi.
Tidak ada perubahan pada MySQL, volume/container operasional atau source Windows.
Tidak ada commit, push atau deployment.

## Autentikasi seluruh role

Login existing sebenarnya sudah menggunakan username/password untuk Mahasiswa,
Dosen, Admin Akademik dan Pimpinan Yayasan. Implementasi ini mempertahankan Auth::attempt,
hashing Laravel, regenerasi session, remember token, invalidasi session/logout,
redirect berdasarkan role, pemeriksaan intended URL dan throttle enam login/menit.
Tidak ada fallback email atau NIM. Label login tetap Username dan Password.
NIM hanya atribut profil akademik; kolom nama/NIM pada pencarian peserta bukan
credential login.

Normalisasi bersama `UsernameNormalization::canonical` menggunakan trim dan
lowercase UTF-8. Model User menormalisasi assignment username; controller login
menormalisasi input dengan aturan sama. Username unik secara global melalui
unique index users.username existing, tanpa pemisahan namespace per role.
Factory membuat username fixture acak yang independen dari email/NIM; akun Test
User baru di DatabaseSeeder memiliki username eksplisit. Credential email yang
masih digunakan untuk lookup seeder bukan mekanisme login.

Migration `2026_11_09_000000_normalize_existing_usernames.php` melakukan preflight
seluruh username sebelum update apa pun, di dalam transaksi. Benturan sesudah
normalisasi atau panjang lebih dari 50 ditolak tanpa perubahan parsial. Username
valid dipertahankan dalam bentuk canonical; user ID, hash password, role dan FK
profil tidak berubah. Null/blank tetap null, **tidak diisi NIM/email/nama rekaan**.
Rollback migration tidak bisa mengembalikan kapitalisasi lama; down tidak menghapus
akun. Perlu snapshot dan review saat migrasi akun nyata, bukan penerapan otomatis.
Raw SQL/bulk account provisioning harus mengikuti normalisasi yang sama; jalur
pembuatan akun baru publik tidak ditambahkan.

Pada SQLite saat ini, satu akun fixture lama masih memiliki username null dan
tidak dapat login sampai mendapat username yang dipilih/disetujui secara eksplisit.
Akun itu tidak ditimpa. Seluruh akun existing yang sudah valid tetap tersedia.
Migration hanya diterapkan pada SQLite development dan SQLite :memory: QA;
MySQL dan migrasi akun produksi **belum diuji/dijalankan**.

## Isolasi, backup dan ownership

Identitas tetap melalui `WslDevelopmentIdentity::verify`: environment local,
driver SQLite, DB_URL kosong, path absolut dengan tag manifest, file bukan symlink
dan PRAGMA database_list aktual harus cocok. Manifest berada di
storage/app/private/wsl-dev-runtime.json, ignored Git.

Inventaris sebelum write: 5 user, 2 mahasiswa sintetis, 1 dosen, 1 program,
1 semester, 1 kelas, 1 KRS/detail/nilai dan 3 analisis QA sebelumnya. Seluruhnya
berasal dari fixture runtime yang sebelumnya dibuat pada database baru kosong,
bukan salinan data operasional. Registry synthetic_student_ids diverifikasi ulang;
wrapper menolak mahasiswa, dosen atau akun yang tidak dikenali.

Snapshot konsisten read-only dibuat sebelum migration/fixture:
`qa-artifacts/auth-fixtures/before-fixtures.sqlite` (ignored, tidak di-commit).
Fixture baru dibuat dalam satu transaksi; error akan rollback. Registry ownership
`academic_fixture` pada manifest mencatat ID mahasiswa/dosen/semester/kelas/nilai.
Rerun hanya memverifikasi dan memakai registry, tanpa mereset nilai atau password.
Username/NIM/kode yang sudah dipakai tanpa ownership yang sah menyebabkan penolakan,
bukan adopsi atau overwrite. Bukti rerun dan tes idempotensi lulus.

Jika perlu rollback data fixture, tinjau snapshot dan hentikan hanya runtime dev
sebelum restore yang disetujui; verifikasi lagi identitas file. Jangan menyalin
snapshot ini ke DB operasional, menjalankan migrate:fresh pada target yang belum
terverifikasi, atau menghapus data existing untuk mengatasi konflik. Snapshot tidak
direstore pada sesi ini; semua akun dan hasil QA sebelumnya dipertahankan.

## Hendrik, Jovian dan data sintetis

- Username **mahasiswa**, nama Hendrik, role Mahasiswa; NIM **24161033** hanya pada
  profil akademik. Password fixture sesuai instruksi lokal pengguna, di-hash.
- Username **dosen**, nama Jovian, role Dosen; NIDN **SIM-JOVIAN-001** untuk demonstrasi,
  bukan identitas resmi dosen. Password fixture sesuai instruksi lokal pengguna, di-hash.
- Kedua akun dan password sederhana itu hanya dibuat melalui helper development
  yang menolak environment tanpa identitas terisolasi. Helper tidak dipanggil dari
  DatabaseSeeder/production seeder.
- Admin/Pimpinan Yayasan QA memakai akun wsl_admin/wsl_owner existing. Kredensial acak mereka
  tetap di file lokal storage/app/private/wsl-dev-credentials.json, ignored;
  tidak dicetak pada log atau laporan.

Program **WSL-TI / Teknik Informatika** (data demonstrasi sintetis). Tiga semester:
2025-GANJIL, 2025-GENAP dan 2026-GANJIL. Dataset merupakan contoh akademik sintetis,
bukan kurikulum resmi perguruan tinggi. Masing-masing semester memuat lima mata
kuliah, 16 SKS, kelas WSL-A berkapasitas 30, ruang LAB-SIMULASI dan Jovian sebagai
dosen. Mata kuliah meliputi algoritma, matematika diskrit, basis data, jaringan,
statistika, rekayasa perangkat lunak, web dan proyek terkait.

Hendrik memiliki tiga KRS disetujui dengan snapshot pengajuan immutable. Pembuatan
kelas → addClass → submit → keputusan Admin memakai service existing; GradeEntry
→ GradeFinalization juga memakai service existing. Jam simulasi lokal digunakan
untuk riwayat semester, lalu dipulihkan. Aktivasi/window semester hanya diubah
sementara di dalam transaksi untuk replay workflow, lalu dikembalikan; metadata
semester existing dipertahankan. Tanggal semester yang sebelumnya belum ada diisi
untuk fixture kalender. Tidak ada kebijakan KRS atau konversi nilai baru dibuat.

Hasil: **15 kelas/detail/nilai**, **14 final + 1 draft**, **120 catatan kehadiran**
(8 per kelas, hadir/izin/alpha existing). Tanggal kehadiran berada dalam semester;
zero foreign-key violations, kapasitas tidak dilanggar. Draft proyek semester kini
tetap dapat diedit oleh Jovian tetapi tidak masuk IPS/IPK. Grade final terkunci.
Riwayat nilai di sini adalah catatan per semester dan revision guard existing,
bukan tabel audit perubahan nilai baru.

Menurut kalkulasi existing: IPS terbaru **3.63**, IPK **3.26**, **14 nilai final valid**.
Skala huruf existing dipakai secara eksplisit; angka tidak otomatis diterjemahkan
ke huruf melalui threshold baru. Dataset berisi contoh riwayat sejak 2025, bukan
klaim seluruh semester sejak angkatan 2024 lengkap. Data yang belum ada tetap
ditampilkan sebagai belum tersedia.

## Groq dan token efficiency

Hendrik berada dalam allowlist fixture sintetis manifest. Simulasi menggunakan
HTTP Client fake, key proses QA sintetis dan preventStrayRequests. Satu hasil
tersimpan dengan tiga rekomendasi; fingerprint identik direuse dengan ID yang sama.
Payload hanya indikator numerik agregat, tidak mengandung username/NIM/email/nama,
nomor telepon atau ID personal. Dashboard/refresh/Pimpinan Yayasan tidak memicu provider.
Mahasiswa lain tidak menerima penjelasan/rekomendasi Hendrik.

Metadata baru menyimpan `transport=mock|live` di ringkasan. Hasil Hendrik diberi
label **Hasil simulasi pengujian** pada Mahasiswa dan Pimpinan Yayasan, sehingga mock tidak
disajikan sebagai hasil provider nyata. Skor/kategori tetap null; klasifikasi resmi
NEEDS SPECIFICATION. Hasil QA lama tetap dipertahankan, tidak ditulis ulang.

**Live Groq aktual: 0 request pada sesi ini; sesi integrasi sebelumnya juga 0.**
Tidak ada izin live eksplisit yang belum digunakan dalam percakapan ini, sehingga
ketentuan kondisional satu request tidak diaktifkan. GROQ_LIVE_ENABLED tetap false.
Tidak ada retry live, pembacaan/pencetakan key atau pengiriman data nyata.
Metadata token pada mock merupakan fixture, **bukan penggunaan/billing nyata**;
reuse dan batas respons diverifikasi, penghematan token live belum terukur.

## QA dan hasil

Bukti lokal di qa-artifacts/auth-fixtures/ (ignored):

- sqlite-final.log: **133 tes / 2242 assertions PASS** pada SQLite :memory:.
- Username/fixture targeted: **4 tes / 58 assertions PASS**, termasuk username
  canonical unik, NIM/email ditolak sebagai login, hash password, benturan migrasi
  tanpa partial write, konflik NIM, idempotensi, FK, kalender, IPS/IPK, role,
  akses kelas asing dan proteksi grade final.
- http-summary.json: **7 HTTP checks PASS**, memakai cookie dan CSRF nyata:
  CSRF wajib, reuse, identifier mahasiswa asing ditolak, Pimpinan Yayasan tidak boleh generate,
  edit/finalisasi ulang nilai final ditolak, dosen lain ditolak.
- assets.json: **10 file manifest HTTP 200 PASS**.
- Playwright + Chrome **156.0.8078.81**, existing cache tanpa instalasi:
  **90 capture / 123 pemeriksaan PASS**, lima viewport 375×812, 390×844,
  768×1024, 1366×768 dan 1920×1080. Seluruh role login/logout dengan username,
  redirect/session rotation, dashboard, KRS, nilai, kehadiran, risiko/rekomendasi,
  kelas/peserta/profil dosen, edit draft dan isolasi mahasiswa/dosen diperiksa.
- Chrome DevTools Protocol melalui CDP session Playwright: Network/Runtime/
  Performance aktif, metrics diambil; tidak ada exception runtime atau request
  frontend ke Groq. Tidak menggunakan MCP DevTools terpisah yang tidak terekspos.
- Seluruh capture HTTP 200, tanpa overflow dokumen, ID duplikat atau label input
  yang hilang. Tidak ada console error/5xx pada matriks. CSS/asset juga diuji HTTP.
  Screenshot risiko Hendrik dan nilai Jovian pada mobile diperiksa visual.
- Ekspektasi koma desimal pada harness awal dikoreksi mengikuti format titik
  existing. Retest IPS/IPK memverifikasi tepat 3.63/3.26; kegagalan awal tetap
  disimpan di browser-summary-initial.json, hasil akhir merujuk ips-retest.json.
- Edit draft melalui browser diuji 79→80→79; status tetap draft dan IPS/IPK tidak
  berubah. Upaya mutasi grade final ditolak; tidak ada data final dihapus/reset.
- PHP syntax **129 file PASS**, Pint **132 file PASS**, Vite **127 modules PASS**.
- MySQL QA **NOT RUN**: tidak ada instance QA terisolasi yang tersedia/terverifikasi;
  MySQL operasional tidak digunakan. Tidak ada pengurangan assertion existing.

Batas: bukan full WCAG, browser fisik/Safari/Firefox, load test atau pentest penuh.
Remaining blockers: persetujuan live smoke test, validitas key/quota provider yang
belum diuji, klasifikasi/metode risiko resmi, migrasi akun nyata dan penamaan akun
legacy tanpa username, serta pengujian migration pada MySQL QA terisolasi.

## Menjalankan ulang fixture

Runtime dan fixture sekarang sudah tersedia. Untuk verifikasi idempotensi lokal:

```sh
cd /path/to/UniNalar-main
docker exec "uninalar-wsl-dev-<runtime-tag>" php scripts/wsl-academic-fixtures.php
```

Perintah ini menolak target yang tidak sesuai manifest dan tidak boleh diarahkan
ke container operasional. Akun lama dan nilai yang diedit secara sah tidak direset.
Tidak perlu melakukan migration fresh, instalasi, perubahan Compose atau volume.

Perubahan source: UsernameNormalization, mutator User, LoginController, factory,
username Test User baru pada DatabaseSeeder, migration normalisasi; helper
DevelopmentAcademicFixtures dan wrapper guarded; metadata transport Groq beserta
label mock pada dua view; tests UsernameAcademicFixtures/Groq, serta dokumentasi.
Source Windows dan konfigurasi environment operasional tidak diubah. Semua
perubahan sesi ini belum di-stage; audit Git ulang sebelum commit manual.

## Pembaruan: empat akun demo lokal

Fixture khusus `DevelopmentDemoAccounts` dan wrapper `scripts/wsl-demo-accounts.php`
menyediakan username **mahasiswa** (Hendrik, NIM 24161033), **dosen** (Jovian),
**admin** (Luiz), dan **pimpinan** (Liwaru, Pimpinan Yayasan). Kredensial demo lokal
mengikuti instruksi final pengguna dan tidak dicetak di dokumentasi ini. Akun wsl_admin/wsl_owner lama tetap dipertahankan.

```sh
# Hanya container development WSL yang sudah diverifikasi:
docker exec "uninalar-wsl-dev-<runtime-tag>" php scripts/wsl-demo-accounts.php
```

Wrapper memverifikasi ulang identitas SQLite sebelum write, memakai lock provisioning,
transaksi, serta registry demo_accounts pada manifest lokal. Akun Hendrik/Jovian
harus cocok dengan profil fixture akademik. Username baru yang sudah dimiliki akun
lain ditolak tanpa adopsi/overwrite; role akun yang sudah dimiliki juga harus cocok.
Rerun tidak menggandakan akun atau mengganti hash yang sudah sesuai. Bila password
akun demo yang sah berbeda, hanya password akun milik fixture itu diperbaiki dan
remember token dihapus. Hash password memakai cast hashing Laravel, bukan plaintext.

Helper menolak production dan database tanpa identitas development yang sah;
pengujian hanya boleh memakai SQLite :memory:. Tidak dipanggil dari DatabaseSeeder,
startup, migration atau seeder production. Fixture akademik existing kini mengenali
ID akun demo dalam registry sehingga kedua wrapper tetap dapat dijalankan ulang.
Snapshot read-only sebelum provisioning ada di qa-artifacts/demo-accounts/
before-demo.sqlite (ignored). Tidak ada migration baru atau perubahan .env.

Hasil QA terbaru disimpan di qa-artifacts/demo-accounts/ (ignored):

- browser-summary.json: Playwright Chrome existing dan CDP Network/Runtime/
  Performance; empat login/logout, redirect dashboard, rotasi cookie session,
  seluruh GET dashboard lintas role, mutation gate Admin dan akses setelah logout.
  **52 pemeriksaan dan 8 capture PASS**, tanpa console/runtime/network error.
  Screenshot desktop 1366×768 dan mobile 375×812 untuk setiap role.
- sqlite-final.log: **137 tes / 2310 assertions PASS** pada SQLite :memory:.
- Tes memeriksa hashing, idempotensi beserta hash yang tidak berubah, role scope,
  konflik akun tanpa partial write, perbaikan password hanya untuk akun milik demo,
  pembersihan remember token dan penolakan production.
- Build tidak diulang: tidak ada perubahan frontend atau asset pada pembaruan akun.
- Tidak ada live Groq, MySQL operasional, commit, push atau deployment.


## Ketentuan final akun development — 9 November 2026

Instruksi ini menggantikan pemetaan username terdahulu. Registry lama dimigrasikan
ke empat username di atas tanpa mengganti ID akun maupun foreign key. NIDN Jovian
`SIM-JOVIAN-001` adalah identitas demonstrasi sintetis (maksimal 20 karakter),
bukan nomor resmi. Helper menolak konflik username maupun NIDN sebelum write dan
menjaga hash yang sudah sesuai saat rerun. Nama Hendrik, Jovian, Luiz, dan Liwaru
tetap. Label UI role keempat adalah **Pimpinan Yayasan**; key `owner`, level 4 dan
route `/dashboard/owner` tetap read-only.

Seeder sintetis `AcademicDemoSeeder` adalah dataset terpisah untuk testing. Seeder
ini tidak boleh menimpa akun WSL yang kebetulan memakai username sama; konflik
email/level ditolak dalam transaksi. Jangan menjalankan seeder umum untuk mengganti
akun development existing; gunakan wrapper terverifikasi di atas.

Profil lengkap masih tersedia untuk Mahasiswa dan Dosen. Admin/Pimpinan Yayasan
memakai identitas akun pada sidebar bersama; tidak ada route profil atau edit
kontak baru. Hasil verifikasi final ada pada
[laporan final akun dan profil](final-demo-accounts-cross-role-profiles.md).


## Fokus demo akademik dan CRUD Admin

Skenario utama memakai Hendrik–Jovian dengan lima kelas per semester dalam tiga
semester. Program studi utama ditampilkan sebagai Teknik Informatika; suffix
teknis pada nama fixture tidak diperlukan. Data tetap sintetis. Record pendukung
QA yang berelasi dipertahankan; angka dashboard bukan target satu mahasiswa/dosen.
Refinement nama program tidak mengubah kode, ID, nilai, SKS, kehadiran, IPS/IPK,
atau analisis historis. Lihat
[laporan data demo dan CRUD Admin](realistic-demo-admin-crud-refinement.md).
