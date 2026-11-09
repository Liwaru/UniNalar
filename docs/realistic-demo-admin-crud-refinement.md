# Data demonstrasi akademik dan refinement CRUD Admin

9 November 2026, Asia/Jakarta. URL development `http://localhost:8140`.

**Playwright MCP PASS; Chrome DevTools MCP PASS.** Perubahan menerapkan filter dan tombol aksi bersama untuk enam CRUD, merapikan nama program utama, serta mempertahankan semua record dan riwayat. Seluruh data yang dibahas di sini adalah fixture sintetis, bukan data universitas yang telah diverifikasi.

## Kondisi data dan skenario utama

| Entitas | Jumlah sebelum/sesudah | Skenario utama | Pendukung yang dipertahankan |
|---|---:|---|---|
| Mahasiswa | 3 | Hendrik, NIM 24161033, akun `mahasiswa` | Alya dan Bima dari fixture QA |
| Dosen | 2 | Jovian, akun `dosen`, NIDN demonstrasi SIM-JOVIAN-001 | Fajar dari fixture QA |
| Program studi | 2 | WSL-TI / Teknik Informatika | QAEC646631 / Teknologi Informasi |
| Semester | 3 | 2025-GANJIL, 2025-GENAP, 2026-GANJIL | Semester terakhir juga digunakan fixture pendukung |
| Mata kuliah / kelas | 16 / 16 | 15 mata kuliah dan 15 kelas diampu Jovian | Satu mata kuliah/kelas pendukung |
| KRS / detail | 4 / 16 | Tiga KRS Hendrik, masing-masing lima kelas / 16 SKS | Satu KRS/detail pendukung |
| Nilai | 16 | 14 final dan satu draft milik Hendrik | Satu nilai pendukung |
| Kehadiran | 120 | Delapan pertemuan × lima kelas × tiga semester | Tidak dibuat catatan tambahan |
| Analisis tersimpan | 6 | Tiga analisis Hendrik | Tiga analisis Alya |

Hendrik dan Jovian menggunakan program yang sama; kelas terhubung ke mata kuliah, dosen dan semester yang sesuai. Ketiga KRS Hendrik berstatus disetujui. SKS tiap semester 16, dihitung dari mata kuliah yang terhubung. Pemeriksaan foreign key tidak menemukan masalah; tidak ada detail KRS yang semester kelasnya berbeda dari semester KRS.

Nama mata kuliah yang sudah tersedia mencakup Algoritma dan Pemrograman, Matematika Diskrit, Basis Data, Jaringan Komputer, Rekayasa Perangkat Lunak, Pemrograman Web dan proyek terkait. Data tidak diisi ulang atau direkayasa untuk mengubah hasil analisis.

Hasil service akademik existing, dari catatan nilai final:

| Semester | IPS | IPK sampai semester tersebut |
|---|---:|---:|
| 2025-GANJIL | 2,84 | 2,84 |
| 2025-GENAP | 3,41 | 3,13 |
| 2026-GANJIL | 3,63 | 3,26 |

Nilai, summary dan history diperiksa identik sebelum/sesudah refinement. Angka tidak diambil dari teks Groq. Analisis historis beserta snapshot, faktor, rekomendasi dan model tetap sama.

Skenario **satu mahasiswa utama dan satu dosen utama** sudah tersedia tanpa menghapus atau menyembunyikan record. Jumlah global **bukan** satu mahasiswa/satu dosen: fixture pendukung memiliki relasi kelas, KRS, nilai dan analisis sehingga tidak aman dianggap sampah sementara. Tidak ditemukan alasan untuk menghapusnya secara otomatis. Demonstrasi dapat dimulai dari akun `mahasiswa`/`dosen` atau pencarian biasa Hendrik/Jovian pada Admin; tidak ada default filter manipulatif atau angka dashboard statis.

## Perubahan data yang terbatas

Program ID 2 / kode WSL-TI dirapikan dari “Teknik Informatika (fixture sintetis)” menjadi **Teknik Informatika**. Hanya `nama` dan `updated_at` row tersebut berubah. Konteks sintetis tetap dinyatakan secara singkat pada dashboard Admin development dan dokumentasi.

`DevelopmentAcademicFixtures` menghasilkan nama bersih untuk fixture baru serta mengenali nama lama pada program yang memang dimiliki fixture terverifikasi. Rerun hanya memperbaiki nama lama yang diketahui; nama lain tidak ditimpa. Guard NIM, username, NIDN dan kode kepemilikan tetap. Eksekusi memakai helper dengan owned IDs; wrapper yang menjalankan migration/seeder **tidak** dipanggil.

Tidak ada perubahan identitas akun, kredensial, NIM/NIDN, kode prodi, relasi, semester, SKS, nilai, kehadiran atau hasil AI. Nama Hendrik, Jovian, Luiz dan Liwaru tetap.

## Perbaikan UI bersama

Enam daftar memakai `dashboard/admin-akademik/resource-index.blade.php`: Program Studi, Semester, Mata Kuliah, Kelas Kuliah, Dosen dan Mahasiswa.

- Paragraf “Kelola … operasional. Data demo tidak ditampilkan dalam daftar ini.” dihapus dari markup, tanpa paragraf pengganti. Judul halaman dan tombol Tambah tetap.
- Cari data kini memakai wrapper `admin-filter-field` yang sama dengan dropdown: label di atas, kontrol penuh dalam wrapper, gap 12px dan tinggi 44px. Flex wrap menyesuaikan jumlah filter; mobile satu kolom.
- Tombol submit rata bawah dengan kontrol pada barisnya. Tidak ada label kosong untuk alignment.
- GET, parameter, kondisi reset dan komponen `<x-filter-reset>` tetap; tidak dibuat CSS reset baru.
- Kontrak `.admin-row-action` digunakan untuk ketiga aksi. Detail memakai outline netral, Ubah outline primary, Hapus outline danger dengan surface putih. Tinggi 44px, radius token 8px, gap 8px, hover dan focus ring jelas. Tombol tidak memakai ikon dekoratif.
- Link Detail/Ubah tetap link ke record yang sesuai. Hapus tetap POST dengan override DELETE, CSRF dan `confirm('Hapus data ini?')` existing. Route, authorization dan backend CRUD tidak berubah.
- Tabel mobile tetap dapat digulir horizontal di region tabel; tidak mendorong lebar dokumen.
- Helper dashboard Admin development sekarang menyatakan data demonstrasi sintetis, menggantikan kalimat yang dapat memberi kesan bahwa fixture development bukan data demo. Jumlah tetap berasal dari query existing.

Filter Pengajuan KRS dan roster kelas sudah memakai label di atas; keduanya ikut menggunakan spacing form bersama tanpa mengubah penjelasan alur keputusan. Panduan field wajib pada create/edit tetap dipertahankan.

## File yang berubah pada tugas ini

1. `resources/views/dashboard/admin-akademik/resource-index.blade.php` — label pencarian, penghapusan intro dan kontrak aksi tabel.
2. `resources/css/admin-academic.css` — filter responsive serta tombol outline bersama.
3. `resources/views/dashboard/admin-akademik/index.blade.php` — konteks data demonstrasi yang akurat.
4. `app/Services/DevelopmentAcademicFixtures.php` — nama program utama dan refinement idempotent.
5. `tests/Feature/UsernameAcademicFixturesTest.php` — assertions nilai/history/summary tetap setelah refinement nama.
6. `docs/development-authentication-fixtures.md` — nama program dan panduan fokus demo terbaru.
7. Laporan ini.

Artefak QA baru berada di folder ignored `qa-artifacts/admin-crud-refinement`. Perubahan source lain yang sudah ada sebelumnya tidak di-reset atau di-stage.

## Playwright MCP aktual

Tool `mcp__playwright__browser_run_code_unsafe` digunakan langsung dengan browser/page milik sesi MCP. Tidak memakai library/browser pengganti atau CDP langsung.

- Dashboard Admin diperiksa: jumlah 3/2/2/3/16/16 tetap sesuai data, dengan konteks sintetis.
- **30 render daftar**: enam CRUD × 375×812, 390×844, 768×1024, 1366×768 dan 1920×1080. Semua label berada di atas kontrol, tinggi input/select/submit/aksi 44px, tanpa overflow dokumen.
- Desktop filter yang muat satu baris memiliki bottom yang sama; tablet dan filter kelas yang lebih banyak membungkus ke baris berikutnya secara natural. Mobile satu kolom.
- Pencarian keenam CRUD mengembalikan hasil sesuai query, reset kembali ke URL dan nilai default.
- Filter status nonaktif pada Program Studi mengembalikan hasil kosong yang sesuai dan reset mengembalikan status Semua.
- Pagination Mata Kuliah mempertahankan `q=a` saat berpindah ke `page=2`.
- Detail, edit dan create keenam resource berhasil dibuka, dengan record URL yang sesuai. Tidak menyimpan form existing pada development.
- Keyboard: pencarian → dropdown, serta Detail → Ubah → Hapus lulus.
- Pengajuan KRS tidak memiliki overflow pada pemeriksaan tambahan.
- Tidak ada page error atau request gagal pada pemeriksaan daftar/navigasi tersebut.

Screenshot before: `.playwright-mcp/admin-crud-before-{program-studi,mata-kuliah}-{1366,375}.png`.
Screenshot after: `.playwright-mcp/admin-crud-after-{program-studi,semester,mata-kuliah,kelas-kuliah,dosen,mahasiswa}-{375,390,768,1366,1920}.png`.

Before menunjukkan Cari data di samping input dan aksi berupa teks. After menunjukkan label seragam di atas kontrol, tombol outline yang mudah dibedakan, dan nama program tanpa suffix teknis. Hasil desktop/mobile ditinjau secara visual, selain pengukuran DOM.

### Pengujian mutatif QA yang benar-benar terisolasi

`AdminCrudBrowserFixtureTest` memakai SQLite `:memory:`, RefreshDatabase, HTTP fake dan larangan stray HTTP request. Test menjalankan create, duplikasi, update, detail dan delete untuk keenam master; memverifikasi penyimpanan, pesan error/sukses serta proteksi penghapusan program berelasi. **1 test / 124 assertions PASS**.

Respons HTML Laravel dari test itu kemudian dipakai oleh Playwright dalam context QA terpisah. Seluruh request non-GET diintersep/ditolak di context, sehingga tidak dapat mencapai database development. Browser menguji native required validation, error duplikasi, old input, edit/save, detail, cancel/accept konfirmasi hapus dan feedback sukses untuk enam CRUD. Hasil: 24 request mutatif diintersep, **nol mutasi backend development**, cancel mengirim nol POST.

Pemeriksaan persetujuan otomatis sempat menolak rencana klik Hapus pada halaman development persisten karena dialog dismiss bisa gagal dan menghapus fixture. Tindakan tersebut tidak dijalankan. Verifikasi dialihkan ke fixture terisolasi di atas; pada halaman existing hanya markup method/CSRF/konfirmasi yang dibaca. Tidak ada kebutuhan persetujuan tambahan untuk pengujian yang sudah selesai ini.

## Chrome DevTools MCP aktual

Tool yang dipanggil langsung: `mcp__chrome_devtools__new_page`, `list_pages`, `evaluate_script`, `navigate_page`, `emulate`, `list_console_messages`, `list_network_requests`, `close_page`.

Program Studi diperiksa pada lima viewport; lima CRUD lain masing-masing pada 375 dan 1366 px. Total **15 sampel computed layout**. Console keenam halaman kosong; seluruh request dokumen/font/CSS/JS/gambar tercatat HTTP 200. CSS Admin baru termuat.

Computed values pada desktop Program Studi:

- Font Plus Jakarta Sans; label muted rgb(82,97,120).
- Input/select tinggi 44px, radius 8px, surface putih dan border rgb(203,213,225).
- Input/select/submit bottom 304,5625px, gap 12px.
- Detail: teks rgb(51,65,85), border netral.
- Ubah: teks dan border rgb(37,99,235).
- Hapus: teks dan border rgb(180,35,24), background putih.
- Table region `overflow-x:auto`; scroll di dalam tabel pada mobile, tanpa overflow dokumen.

Pemeriksaan visual lintas empat dashboard pada 375/1366 px mempertahankan font dan layout existing. Dashboard Mahasiswa sempat terbaca lebar saat resize sebelum Chart.js selesai menyesuaikan; retest menunggu font/layout stabil dan lulus pada kelima viewport serta resize balik ke 375. Tidak ada perubahan source dashboard Mahasiswa/Dosen/Pimpinan untuk menutupi kondisi sementara tersebut.

## Regression dan keamanan

- PHPUnit seluruh suite SQLite memory: **149 tests / 2.455 assertions PASS**, termasuk CRUD enam master, filter/search, authentication/authorization, proteksi relasi, KRS, finalisasi nilai, IPS/IPK dan Groq mocked.
- QA fixture CRUD: **1 test / 124 assertions PASS**.
- Laravel Pint: **2 file PHP PASS**.
- PHP syntax: **2 file PHP dan 2 hasil kompilasi Blade PASS**.
- Vite build: **PASS**, 2,87 detik.
- `git diff --check`: **PASS**, staging kosong.

Sebelum mutasi, database development diverifikasi melalui `WslDevelopmentIdentity::verify()`: environment local, path/manifest, driver SQLite aktual dan PRAGMA sesuai. Backup read-only konsisten dibuat di `qa-artifacts/admin-crud-refinement/before.sqlite`, permission 0600 dan ignored; tidak direstore otomatis.

Perbandingan akhir seluruh row/kolom terhadap backup: **23 dari 24 tabel identik**. Satu row `program_studi` ID 2 hanya berubah pada `nama`/`updated_at`. Jumlah row seluruh tabel tetap, foreign key check kosong, dan tidak ada semester KRS/kelas yang tidak cocok. Tabel users juga identik; tidak ada perubahan password/token akun pada tugas ini. Seluruh enam analisis historis, faktor dan rekomendasi tetap identik.

Hash `.env`, konfigurasi Groq, route dan ledger live Groq tetap sama. Tidak ada live Groq, MySQL operasional, migration/seeder operasional, Docker operasional, source Windows, sudo, dependency baru, konfigurasi MCP, staging/commit/push/deployment.

Bukti lokal: `mcp-verification.json`, `academic-summary.json`, `integrity.json`, `before-tables.json`, `task.diff`, `phpunit.log`, `browser-fixture-tests.log`, `fixture-refinement-tests.log`, `pint.log`, `syntax.log`, `build.log` pada folder QA ignored di atas.

## Persetujuan tambahan

Tidak diperlukan untuk perubahan yang telah diterapkan. Pengurangan database global menjadi tepat satu mahasiswa, satu dosen dan satu program akan memerlukan keputusan cleanup terpisah, inventaris dampak pada relasi serta persetujuan eksplisit. Tidak dilakukan, tidak disimulasikan lewat angka statis, dan tidak disembunyikan dengan filter default. Skenario fokus Hendrik–Jovian sudah aman digunakan dengan data pendukung tetap tersedia.
