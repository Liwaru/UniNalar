# Final demo accounts, cross-role profiles, and academic identity

9 November 2026, Asia/Jakarta. Development URL: `http://localhost:8140`.

Implementasi selesai pada SQLite development terisolasi. **Playwright MCP PASS; Chrome DevTools MCP PASS.** Kredensial rahasia/hash tidak dicantumkan dalam laporan ini. Perubahan working tree dari pekerjaan sebelumnya dipertahankan.

## Akun final dan identitas

| Role tampilan | Username | Nama existing | ID existing | Dashboard |
|---|---|---|---:|---|
| Mahasiswa | `mahasiswa` | Hendrik | 6 | `/dashboard/mahasiswa` |
| Dosen | `dosen` | Jovian | 7 | `/dashboard/dosen` |
| Admin Akademik | `admin` | Luiz | 8 | `/dashboard/admin-akademik` |
| Pimpinan Yayasan | `pimpinan` | Liwaru | 9 | `/dashboard/owner` |

Password telah diperbarui sesuai instruksi final pengguna dan disimpan melalui hashing Laravel. Password/hash tidak ditampilkan di frontend atau laporan ini. Tidak ada forced password change. Empat username lama tidak lagi ditemukan pada database dan percobaan login masing-masing ditolak. Akun QA lain yang bukan target tidak diubah.

`DevelopmentDemoAccounts` menerima registry ID lama maupun final, memverifikasi nama, role dan relasi akademik, lalu memeriksa konflik seluruh username/NIDN sebelum write dalam transaksi. Registry lokal kini memakai username final dengan ID yang sama. Eksekusi kedua wrapper `scripts/wsl-demo-accounts.php` menghasilkan database yang byte-identical: tidak ada akun duplikat, reset data, atau pergantian hash yang tidak diperlukan.

`DevelopmentAcademicFixtures` mengenali identitas final dan registry lama untuk rerun, serta menghasilkan username final pada fixture baru. Helper tetap menolak production dan database yang tidak memiliki identitas development terverifikasi; tes fixture dibatasi SQLite memory. Helper tidak dipanggil saat login/reload atau dari seeder production.

`AcademicDemoSeeder` merupakan dataset sintetis terpisah untuk pengujian. Username role keempat kini `pimpinan`; akun sintetis lama `owner` hanya dimigrasikan jika email dan level cocok. Seeder memakai transaksi, mempertahankan hash saat rerun, dan menolak username yang dimiliki akun lain. Seeder umum ini **tidak dijalankan pada database development existing** dalam tugas ini.

## NIDN demonstrasi

Jovian mendapat `SIM-JOVIAN-001`, identitas sintetis demonstrasi, **bukan NIDN resmi**. Schema existing menerima string maksimum 20 karakter, nullable dan unique; validasi Admin menerima string maksimum 20 dan menolak prefix dataset lama `DEMO`. Nilai yang dipilih memenuhi batasan tersebut serta tidak meniru nomor resmi. Tidak diperlukan migration/schema change.

Fixture hanya mengisi nilai null atau mempertahankan nilai sintetis yang sama; NIDN lain maupun konflik unique ditolak. Dashboard dan Profil Dosen menampilkan nilai ini beserta keterangan demonstrasi. ID Dosen, relasi user, program studi, kelas, peserta, kehadiran dan nilai tetap sama.

## Label Pimpinan Yayasan

Sumber label bersama `User::ROLE_LABELS` level 4 kini **Pimpinan Yayasan**. Sidebar, label role/header dan area yang memakai label bersama ikut berubah. Pencarian source pada view aktif/lang tidak menemukan label lama Owner/Kaprodi atau Kepala Program Studi.

Playwright membuka 11 halaman monitoring melalui menu: ringkasan, mahasiswa, dosen, kelas, KRS, nilai, kehadiran, analisis, rekomendasi, tindak lanjut dan laporan. Semuanya HTTP 200, menampilkan label baru, tanpa label lama maupun form POST pada area konten. Role key `owner`, konstanta/level 4, middleware, route, policy dan kemampuan monitoring read-only tidak berubah. README, pedoman UI dan dokumentasi fixture aktif diperbarui; laporan audit lama tetap merupakan catatan historis sesuai tanggalnya.

## Inventaris profil dan hak edit

| Role | Route / controller / view | Data yang ditampilkan | Hak edit |
|---|---|---|---|
| Mahasiswa | GET `/dashboard/mahasiswa/profil`, PUT `/dashboard/mahasiswa/profil/kontak`; MahasiswaDashboardController::profil/updateProfil; `dashboard/mahasiswa/profil.blade.php` | Inisial, Hendrik, role, NIM 24161033, program studi existing, status Aktif, angkatan 2024, jenjang/kode program | Email profil dan telepon saja |
| Dosen | GET `/dashboard/dosen/profil`, PUT `/dashboard/dosen/profil/kontak`; DosenProfilController::show/update; `dashboard/dosen/profil.blade.php` | Inisial, Jovian, role, NIDN demonstrasi, program studi existing, status, jenjang/kode program | Email profil dan telepon saja |
| Admin Akademik | Tidak ada route/controller/view profil tersendiri | Luiz dan role pada sidebar akun existing | Tidak ada edit kontak existing |
| Pimpinan Yayasan | Tidak ada route/controller/view profil tersendiri | Liwaru dan role baru pada sidebar akun existing | Tidak ada edit kontak existing; monitoring tetap read-only |

Program studi fixture tetap “Teknik Informatika (fixture sintetis)”; tidak diubah untuk mempercantik screenshot. Tidak dibuat unit, jabatan, foto, atau informasi organisasi yang tidak tersedia.

Dua profil lengkap mempertahankan komponen bersama `academic-profile-header` dan `academic-profile-details`: avatar inisial, hierarki nama/role, badge status, label–nilai, divider halus dan form maksimal 640px. Desktop memakai dua kolom detail, mobile satu kolom. Tidak ada kotak abu-abu per field, dekorasi atau CSS baru yang tidak diperlukan. Sidebar bersama sudah memberi pola identitas akun yang sama untuk keempat role.

Admin/Pimpinan tidak memiliki halaman akun alternatif yang dapat dijadikan profil lengkap tanpa menambah route/alur. Solusi minimal mempertahankan identitas existing pada sidebar; tidak menambah halaman maupun akses edit secara diam-diam. Jika profil lengkap untuk kedua role diperlukan kelak, itu merupakan fitur tersendiri.

CSRF, PUT, old input, feedback sukses/error, authorization dan validasi kontak tetap. Email nullable/email/max255/unique serta pembatasan domain demo existing; telepon nullable/string/max30. Nama, NIM/NIDN, program, angkatan, status dan role tidak menjadi editable.

## Source yang berubah pada tugas ini

1. `app/Services/DevelopmentDemoAccounts.php` — migrasi akun ber-ID tetap, preflight konflik, NIDN sintetis, idempotensi.
2. `app/Services/DevelopmentAcademicFixtures.php` — fixture final dan kompatibilitas ownership lama.
3. `app/Models/User.php` — label tampilan role keempat.
4. `database/seeders/AcademicDemoSeeder.php` — username final, transaksi, perlindungan akun lain dan hash saat rerun.
5. `resources/views/dashboard/dosen/index.blade.php` — keterangan identitas demonstrasi.
6. `resources/views/dashboard/dosen/profil.blade.php` — NIDN dengan label demonstrasi pada header bersama.
7. `tests/Feature/DevelopmentDemoAccountsTest.php` — ID/relasi, konflik, idempotensi, registry dan guard database.
8. `tests/Feature/UsernameAcademicFixturesTest.php` — ekspektasi fixture final.
9. `tests/Feature/DemoAccountAuthenticationTest.php` — username/label final dan perlindungan seeder.
10. `README.md` — label role publik.
11. `docs/ui-ux-guidelines.md` — label role pada pedoman aktif.
12. `docs/development-authentication-fixtures.md` — pemetaan akun dan identitas demonstrasi terbaru.
13. Laporan ini.

Tidak ada perubahan CSS, navbar, route, schema, authorization atau form/backend akademik pada tugas ini. Komponen profil bersama dari tugas sebelumnya digunakan kembali.

## Bukti MCP aktual

Tool yang benar-benar dipanggil:

- Playwright: `mcp__playwright__browser_tabs`, `mcp__playwright__browser_run_code_unsafe`.
- DevTools: `mcp__chrome_devtools__list_pages`, `new_page`, `navigate_page`, `evaluate_script`, `emulate`, `list_console_messages`, `list_network_requests`, `close_page`.

Playwright menggunakan objek browser/page milik sesi MCP resmi untuk context QA; tidak meluncurkan library/browser pengganti. DevTools menggunakan MCP langsung, bukan CDP biasa.

Hasil:

- Empat login final berhasil, identitas nama sesuai, redirect dashboard sesuai role, logout berhasil, dan akses privat sesudah logout diarahkan ke login.
- Dua belas kombinasi akses dashboard lintas-role ditolak HTTP 403.
- Username lama ditolak. Dua percobaan awal mencapai rate limit existing; setelah jendelanya berakhir, uji ulang menghasilkan error kredensial yang benar. Rate limiting tidak dihapus/dibypass.
- NIDN muncul di Dashboard dan Profil Dosen.
- Keyboard profil: email → telepon → tombol simpan berjalan untuk kedua profil.
- Dua puluh screenshot profil/dashboard di `.playwright-mcp/final-demo-{mahasiswa,dosen,admin,pimpinan}-{375,390,768,1366,1920}.png`.
- Browser native memblokir email tidak valid. HTML error/sukses yang benar-benar dihasilkan Laravel pada fixture SQLite memory dipasang melalui interception QA; duplicate email menampilkan pesan dan mempertahankan old input, lalu feedback sukses tampil. Empat POST browser fixture diintersep, **nol POST kontak ke backend development**. Profil Hendrik/Jovian tidak dimutasi untuk tes form.
- Pemeriksaan network awal mencatat pembatalan body request uji 403 ketika harness segera berpindah halaman. Retest menunggu body selesai: tidak ada request gagal. Ini bukan kegagalan aset aplikasi.

## Responsive dan computed CSS

Kedua MCP memeriksa empat role pada lima viewport:

| Viewport | Detail profil Mahasiswa/Dosen | Lebar form | Overflow horizontal keempat role |
|---|---|---|---|
| 375×812 | Satu kolom | 311px | Tidak ada |
| 390×844 | Satu kolom | 326px | Tidak ada |
| 768×1024 | Dua kolom | 485px | Tidak ada |
| 1366×768 | Dua kolom | 640px | Tidak ada |
| 1920×1080 | Dua kolom | 640px | Tidak ada |

DevTools mengonfirmasi font **Plus Jakarta Sans** loaded; surface rgb(255,255,255), text rgb(20,33,61), border rgb(231,237,243), radius 12px. Identitas memakai flex dan detail memakai grid responsive existing. Console halaman normal keempat role kosong; seluruh request dokumen/font/CSS/JS/gambar yang tercatat berstatus 200. Tidak ada live Groq. Markup yang diperiksa tidak mengandung API key Groq/hash password; tidak ditambahkan hidden user ID, NIDN editable, atau field kredensial pada profil.

## Regression

- PHPUnit seluruh suite Unit/Feature: **149 tests, 2.450 assertions PASS**, SQLite memory. Mencakup authentication, authorization, fixture, profil, KRS, finalisasi nilai dan integrasi Groq mocked.
- Fixture profil QA terpisah: **1 test, 44 assertions PASS**. Menolak perubahan field akademik lewat payload kontak dan akses silang profil; HTTP fake memastikan tidak ada request keluar.
- Laravel Pint `--test`: **7 file PHP PASS**.
- PHP syntax: **7 file PHP dan 4 hasil kompilasi Blade PASS**.
- Vite build: **PASS**, 3,25 detik.
- `git diff --check`: **PASS**; staging kosong.

Tambahan tes baru sempat menemukan perbedaan urutan key Eloquent saat membandingkan atribut sebelum/sesudah reload. Perbandingan diperbaiki dengan menyortir key dan tetap memeriksa seluruh nilai identik; tidak ada assertion keamanan existing yang dilemahkan.

## Isolasi dan integritas

`WslDevelopmentIdentity::verify()` memverifikasi environment local, path/manifest, driver aktual SQLite dan PRAGMA terhadap database development WSL. Backup konsisten SQLite dibuat melalui read-only connection sebelum write di `qa-artifacts/final-demo-profiles/before.sqlite`, permission 0600, ignored. Fingerprint seluruh 24 tabel dicatat sebelum perubahan.

Perbandingan akhir terhadap backup memverifikasi:

- Seluruh jumlah row tetap; tidak ada duplikasi atau penggantian ID.
- Empat row users hanya berubah pada username, password hash, remember token terkait dan timestamp. Logout browser memang merotasi remember token melalui mekanisme Laravel existing.
- Satu row Dosen Jovian hanya berubah pada NIDN dan timestamp.
- **22 tabel lainnya identik**, termasuk mahasiswa, kelas, KRS/riwayat, peserta, kehadiran, nilai, analisis risiko tersimpan, faktor dan rekomendasi. Foreign key check kosong.
- Hash `.env`, `config/services.php`, route dan ledger live Groq tetap identik.
- Tidak ada data MySQL operasional, source Windows, Docker operasional, konfigurasi MCP, dependency, live Groq, staging/commit/push/deployment yang diakses untuk perubahan ini.

Bukti lokal ignored: `qa-artifacts/final-demo-profiles/mcp-verification.json`, `integrity.json`, `allowed-changes.json`, `before-tables.json`, `phpunit.log`, `profile-fixture-tests.log`, `pint.log`, `syntax.log`, `build.log`. Backup dan hasil browser tidak perlu dipublikasikan.

Remaining issues: tidak ada kegagalan pada cakupan yang diuji. Profil mandiri/edit kontak Admin dan Pimpinan belum tersedia secara existing; tidak dianggap sebagai fitur yang diimplementasikan pada tugas ini. Pengujian ini untuk development terisolasi, bukan sertifikasi produksi.
