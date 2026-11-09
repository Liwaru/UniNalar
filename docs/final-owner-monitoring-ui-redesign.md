# Pimpinan Yayasan — monitoring UI dan visualisasi akademik

Tanggal: 9 November 2026. Workspace `/home/hendra_huang/projects/UniNalar-main`.

**FIXED:** polishing sebelas halaman utama Owner beserta presentasi Detail terkait. **PASS:** pemeriksaan lokal dalam cakupan di bawah. **PARTIAL:** font asli saat offline dan kombinasi data/state yang tidak diuji exhaustif. Laporan ini tidak menyatakan seluruh aplikasi atau production telah diverifikasi.

## Kondisi awal

HEAD aktual tetap `fe5f206`, branch `main`. Working tree sudah berisi redesign Admin Akademik dari tugas sebelumnya, termasuk file baru yang belum tracked. Perubahan tersebut dipertahankan; tidak ada reset/checkout atau operasi Git mutatif. `.agents/` tetap tidak disentuh.

Screenshot pengguna dan browser lokal memperlihatkan filter terpisah dari aksi, pencarian tanpa placeholder, paragraf teknis berulang, statistik datar, link Detail kurang jelas, rekomendasi/deskripsi identik dalam dua kolom, serta Laporan dengan header panjang dan banyak kolom.

Sumber data diperiksa sebelum implementasi: `OwnerDashboardController`, `OwnerMonitoringController`, `OwnerAcademicData`, `OperationalAcademicData`, dan `AcademicGradeSummary`. **Tidak ada perubahan controller, service, model, route, middleware, policy, perhitungan akademik, atau query bisnis.** Identifier internal tetap `owner`; haknya tetap read-only.

## Halaman dan cakupan browser

Sebelas halaman utama dan lima Detail berikut diperiksa pada **375×812, 390×844, 768×1024, 1366×768, 1920×1080**: total **80 pemeriksaan layout**.

| Halaman | Route yang diperiksa |
|---|---|
| Ringkasan Akademik | `/dashboard/owner` |
| Monitoring Mahasiswa | `/dashboard/owner/mahasiswa` |
| Monitoring Dosen | `/dashboard/owner/dosen` |
| Monitoring Kelas | `/dashboard/owner/kelas` |
| Monitoring KRS | `/dashboard/owner/krs` |
| Nilai Final | `/dashboard/owner/nilai` |
| Kehadiran | `/dashboard/owner/kehadiran` |
| Analisis Risiko Tersimpan | `/dashboard/owner/analisis-risiko` |
| Rekomendasi Tersimpan | `/dashboard/owner/rekomendasi` |
| Tindak Lanjut | `/dashboard/owner/tindak-lanjut` |
| Laporan Akademik | `/dashboard/owner/laporan` |
| Detail mahasiswa | `/dashboard/owner/mahasiswa/1` |
| Detail dosen | `/dashboard/owner/dosen/1` |
| Detail kelas | `/dashboard/owner/kelas/16` |
| Detail KRS | `/dashboard/owner/krs/4` |
| Detail analisis | `/dashboard/owner/analisis-risiko/6` |

ID di atas adalah record development yang tersedia ketika audit, bukan identifier universal. Semua data development merupakan fixture sintetis; tidak diklaim sebagai data universitas nyata.

## Perubahan tampilan

### Toolbar dan microcopy

Filter dipadukan dalam toolbar putih dengan label di atas kontrol, jumlah hasil dari paginator ketika tersedia, dan kelompok Hapus filter/Terapkan filter. Dua/empat field menggunakan grid dua kolom desktop; tiga field menggunakan tiga kolom; tablet adaptif dua kolom dan mobile satu kolom. Footer aksi selalu memiliki baris sendiri yang terstruktur. Tidak ada pencarian otomatis tiap ketikan.

Placeholder mengikuti kemampuan query existing, bukan janji pencarian baru:

| Cakupan | Placeholder |
|---|---|
| Mahasiswa, KRS, Nilai, Kehadiran, Analisis, Rekomendasi, Tindak Lanjut | Cari nama atau NIM mahasiswa |
| Dosen | Cari nama dosen atau NIDN |
| Kelas | Cari kelas atau mata kuliah |
| Laporan | Cari kode atau nama program studi |

Nilai/Kehadiran existing hanya mencari identitas mahasiswa; karena itu tidak diberi placeholder pencarian mata kuliah. Demikian pula Analisis/Rekomendasi tidak menjanjikan pencarian teks AI.

GET, parameter filter, pilihan aktif, pagination, dan komponen **`x-filter-reset` existing** dipertahankan. Ringkasan jumlah memakai `total()`, bukan jumlah baris halaman aktif. Deskripsi halaman dibuat spesifik; footer read-only yang berulang di setiap halaman dihapus tanpa mengubah permission.

Penjelasan penting tetap berada dekat data: IPS versus IPK, peserta kelas bukan mahasiswa unik, cakupan semester, status KRS saat ini versus riwayat, ketidakhadiran yang belum dicatat bukan alpa, dan risiko belum diklasifikasikan bukan risiko rendah. Keterangan sekunder yang panjang ditempatkan dalam disclosure native yang dapat dibuka dengan keyboard.

### Ringkasan dan tabel

Data akademik, Capaian akademik, dan Status operasional memiliki hierarki label/angka yang konsisten. Metrik dikelompokkan dalam panel existing, bukan satu card besar untuk setiap angka. Status operasional menjadi metric rows dengan badge dan angka yang berdampingan; status tidak dikenal tetap netral.

Tabel dibungkus permukaan putih dengan divider halus, whitespace sesuai tipe data, dan scroll lokal. Kode/tanggal tidak dipecah; narasi wrap pada batas kata. Link Detail menggunakan tombol outline kecil berlabel spesifik dan target setidaknya 44 px. Tidak ada tombol Tambah/Ubah/Hapus/Approval/Finalisasi untuk Owner.

Petunjuk geser tabel hanya muncul ketika `scrollWidth > clientWidth`, diperbarui dengan `ResizeObserver`. Container tetap focusable; tombol panah keyboard menggulir tabel secara lokal. Semua kolom/data penting tetap dapat diakses.

### Rekomendasi dan analisis tersimpan

Kolom rekomendasi/deskripsi digabungkan secara presentasi. Jika isinya identik, tidak ditampilkan dua kali. Teks panjang atau deskripsi berbeda menggunakan `<details>`; teks lengkap tetap tersedia di halaman tersebut. Konten asli dan database tidak berubah. Pada fixture development saat audit, teks cukup pendek untuk tampil lengkap inline; tidak dipaksakan disclosure yang tidak diperlukan.

Analisis menampilkan provenance singkat pada daftar, dengan sumber/model/penjelasan lengkap tetap pada Detail existing. Skor tidak tercatat tetap **Tidak tercatat**. Kategori belum tersedia tetap **Belum diklasifikasikan**, memakai warna netral, bukan hijau. Tidak ada perhitungan atau request AI baru ketika membuka halaman.

### Laporan Akademik

Kolom inti: Program studi/kode, Mahasiswa, Kelas, KRS disetujui, Nilai final, Rincian. Data tambahan tetap tersedia dalam disclosure per program: mahasiswa dengan KRS disetujui, KRS menunggu/ditolak, analisis tersimpan dan tindak lanjut. Link Ringkasan program tetap valid dengan konteks semester.

Semua metrik existing dipertahankan; tidak menambahkan ekspor atau route baru. Tabel desktop menjadi lebih mudah dibandingkan, tanpa header panjang yang memaksa scroll. Pada mobile tetap menggunakan scroll lokal. Mahasiswa dihitung unik; KRS, nilai, analisis dan tindak lanjut adalah jumlah catatan sesuai definisi query existing.

### Empty state

Tanpa filter: **Belum ada catatan**. Dengan filter: **Tidak ada hasil sesuai filter**, disertai reset. Ketidaktersediaan schema tetap memiliki informasi tersendiri, tidak dianggap nol. Colspan empty state disesuaikan dengan jumlah kolom masing-masing tabel.

## Tiga visualisasi dan definisi data

Komponen reusable `x-owner.distribution` memakai HTML/CSS server-rendered: label kategori, jumlah, persentase, dan bar proporsi. Tidak memerlukan hover, tooltip, JavaScript chart, animasi atau dependency baru. Label/nilai tersedia sebagai definition list, disertai tabel data akademik existing pada Nilai/Kehadiran. Chart.js memang tersedia dalam proyek; visual sederhana ini tidak memerlukannya.

| Visualisasi | Sumber resmi existing | Unit dan denominator |
|---|---|---|
| Status KRS pada Ringkasan | `$krsStatuses`: `OwnerAcademicData::krs($filters)` dikelompokkan menurut status | Jumlah catatan KRS saat ini; persentase terhadap seluruh catatan KRS dalam filter |
| Distribusi Nilai Final | `$distribution`: query `grades($filters)`, pencarian mahasiswa, agregasi `nilai_huruf` sebelum pagination | Catatan nilai final, **bukan mahasiswa unik**; KRS disetujui, detail diambil, final_at tersedia |
| Komposisi Kehadiran | `$counts`: query `attendance($filters)`, pencarian mahasiswa, agregasi status sebelum pagination | Catatan kehadiran peserta resmi yang benar-benar tersimpan; bukan perkiraan pertemuan yang hilang |

Persentase adalah jumlah kategori dibagi total agregasi tersebut. Pembulatan satu desimal dapat membuat jumlah persentase tampil sedikit berbeda dari 100%. Nilai asli tidak berubah. Key database `alpha` dipresentasikan sebagai **Alpa** tanpa perubahan penyimpanan.

Tidak ada angka dari penjelasan Groq yang diparsing menjadi grafik. Tidak ada tren IPS/IPK, grafik risiko atau perbandingan program tambahan yang dipaksakan. Hanya tiga visualisasi prioritas pengguna yang diterapkan.

Kategori yang tidak ada tidak dibuat seolah catatan baru. Jika total nol, tampil empty state tanpa bar. Jika hanya satu kategori, tampil ringkasan jumlah/persentase tanpa bar dekoratif. Karena data KRS development saat ini hanya memiliki kategori Disetujui, grafik KRS berada pada mode ringkasan; kemampuan empat status diuji dengan fixture memory.

### Pencocokan angka dengan SQLite query-only

Angka browser dibandingkan dengan query resmi dalam koneksi SQLite development ber-**`PRAGMA query_only=ON`**, tanpa perubahan data. Bukti agregasi tanpa identitas pribadi: `qa-artifacts/owner-targeted/aggregates.json`.

| Cakupan | KRS | Nilai final | Kehadiran |
|---|---|---|---|
| Semua data | Disetujui 4 | A 3, AB 4, B 6, BC 1, C 1; total 15 | Hadir 114, Izin 3, Alpa 3; total 120 |
| Semester 2026-GANJIL | Disetujui 2 | A 2, AB 1, B 2; total 5 | Hadir 38, Izin 1, Alpa 1; total 40 |
| Program Teknik Informatika | Disetujui 3 | A 3, AB 4, B 5, BC 1, C 1; total 14 | Hadir 114, Izin 3, Alpa 3; total 120 |

Ketiga kategori visual cocok dengan sumber query untuk semua data serta enam pemeriksaan tambahan program/semester. Pagination Kehadiran halaman 2 mempertahankan `q=Hendrik` dan total komposisi **120**, bukan hanya 20 baris tabel.

## File berubah dalam tugas Owner

Perubahan berikut dibedakan dari pekerjaan Admin sebelumnya yang masih ada dalam working tree:

- Baru: `resources/css/owner-monitoring.css`, `resources/js/owner-monitoring.js`.
- Baru: `resources/views/components/owner/distribution.blade.php`, `status.blade.php`.
- Baru: `tests/Feature/OwnerPresentationTest.php` dan laporan ini.
- Shared entry: `resources/css/student-dashboard.css` mengimpor CSS Owner; `resources/js/form-feedback.js` menambahkan import Owner sambil mempertahankan import Admin sebelumnya.
- View Owner: `index`, `layout`, `students`, `student`, `lecturers`, `classes`, `class`, `krs`, `krs-detail`, `grades`, `attendance`, `risks`, `risk`, `recommendations`, `follow-ups`, `reports`.
- Partial Owner: `filters`, `empty`, `class-table`.

CSS/JS memakai selector Owner yang spesifik. Komponen reset, dashboard shell/sidebar, tokens, pagination, detail-fields dan navigasi Detail existing dimanfaatkan kembali. Tidak membuat komponen filter duplikat atau sidebar baru.

## Playwright MCP — hasil nyata

Tool: **`mcp__playwright__browser_run_code_unsafe`**, memakai browser/context dari MCP, tanpa CLI atau CDP langsung sebagai pengganti.

- Login Owner dan **80 render** halaman/viewport: HTTP 200, tidak overflow dokumen, tidak ada error JS atau asset failure pada matriks akhir.
- Toolbar/placeholder/posisi aksi/target link-button: PASS. Tidak ada kontrol mutatif pada seluruh 80 render.
- Program studi, semester, pencarian dan reset pada sebelas halaman: retained state/reset PASS. Filter status KRS dan kelas Kehadiran PASS.
- Lima link Detail diikuti ke route yang benar; Detail turut diperiksa pada lima viewport.
- Pagination Kehadiran mempertahankan query dan agregasi penuh. Filter kelas tanpa catatan menghasilkan total nol, bukan kehadiran fiktif.
- Empty state Nilai: total nol dan tanpa bar. Tindak lanjut kosong tidak dianggap error.
- Disclosure Laporan dapat dibuka dengan Enter dan menyediakan lima metrik tambahan serta link Ringkasan program.
- Keyboard scroll mobile menghasilkan scroll lokal 40 px, tanpa overflow dokumen.
- Petunjuk scroll hanya tampil jika tabel benar-benar overflow; kondisi diperiksa pada semua render.
- Halaman representatif Admin/Mahasiswa/Dosen pada 390/1366: HTTP 200, tidak overflow/error, tidak memperoleh elemen Owner. Menu aksi Admin tetap dapat dibuka.

Request font eksternal diintersepsi dan dipenuhi lokal oleh harness; tidak diteruskan ke internet. Tidak ada download/install. Harness verifikasi chart sempat mencoba membuka disclosure rekomendasi yang tidak ada karena teksnya pendek; diperbaiki agar memeriksa kebutuhan disclosure terlebih dahulu. Ini bukan kegagalan navigasi/fitur aplikasi.

## Chrome DevTools MCP — hasil nyata

Tool pada namespace `mcp__chrome_devtools__`: **`new_page`**, **`evaluate_script`**, **`emulate`**, **`list_console_messages`**, **`list_network_requests`**, **`close_page`**.

HTML asli lokal dirender setelah link eksternal dikeluarkan dan CSP lokal diterapkan, sehingga tidak memuat font internet. JS/CSS tetap berasal dari localhost. Halaman QA ditutup setelah pemeriksaan.

| Sampel computed | Hasil |
|---|---|
| Nilai desktop 1366 | Grid 491/491 px, tombol 44 px, bar A ±199,6 px dari track 998 px (20%), primary rgb(37,99,235) |
| Nilai mobile 390 | Grid satu kolom 326 px, chart 326 px, ratio bar ±0,20; tabel 810 px dalam container 324 px dengan hint aktif |
| Kehadiran desktop | Bar 95%/2,5%/2,5%; total 120; warna success/warning/danger sesuai tokens |
| Laporan desktop | Grid filter tiga kolom 322 px; tabel muat 996 px, hint tidak muncul; disclosure lima metrik dan link 44 px |
| Font | Plus Jakarta Sans dengan fallback existing dideklarasikan; glyph memakai fallback selama offline |
| Console/network | Tidak ada error/warning pada sampel akhir; asset localhost 200, redirect login 302 normal, tidak ada request eksternal |

## Regression dan read-only safety

Pengujian dibatasi bagian terdampak; suite global penuh tidak diulang.

| Pemeriksaan | Hasil |
|---|---|
| OwnerModuleTest + PublishedAcademicSummaryTest + FinalSystemSecurityTest | 17 skenario; run awal 16 PASS, satu gagal pada teks empty state. Source diperbaiki tanpa melemahkan assertion; rerun hanya kasus tersebut PASS (1 test/8 assertions) |
| OwnerPresentationTest | Dua tes awal PASS, 30 assertions; satu tes tambahan empat status KRS PASS, 11 assertions |
| Groq mocked, GET/navigasi/saved reuse | Dua tes PASS, 37 assertions; HTTP fake, tidak menghubungi provider |
| Cakupan backend | Filter/tampering, pagination/agregasi penuh, IPS/IPK, lintas program, KRS/roster, provenance, escaping dan privacy, query growth, read-only authorization |
| PHP/Blade | 23 Blade Owner/component dikompilasi dan syntax PASS; tes PHP baru syntax PASS |
| Laravel Pint | File tes Owner baru PASS pada pemeriksaan final |
| JS syntax | Modul Owner PASS |
| Vite build | PASS, build final 4,70 detik; dependency existing |
| Git whitespace/staging | `git diff --check` PASS; staging kosong |

Total **22 skenario backend unik** telah lulus setelah koreksi terarah; angka run lama tidak dipakai sebagai hasil run baru. Test authorization existing membuktikan non-Owner tidak dapat membuka monitoring, POST pada route read-only ditolak, dan Owner tidak dapat menulis melalui endpoint Admin/Dosen. Tidak ada perubahan permission.

Sebelum/sesudah: **24/24 tabel development identik**, foreign-key check kosong. Semua backend, route, `.env`, konfigurasi layanan, ledger Groq, dan source Admin yang difingerprint tetap identik. Dari 67 file yang dicatat, satu perubahan shared yang disengaja adalah tambahan import pada `form-feedback.js`; 66 lainnya identik. File import tersebut tetap mempertahankan implementasi Admin. Tidak ada credential atau isi `.env` dicetak.

Data Hendrik/Jovian, akun demo, nilai, KRS, riwayat analisis/rekomendasi tersimpan tetap utuh. Mutasi tes backend hanya pada SQLite `:memory:`. Tidak ada request Groq live, perubahan Docker/MCP/APP_KEY/source Windows, Git add/commit/push/merge/reset/clean, atau deployment.

## Screenshot dan evidence

| Halaman | Sebelum desktop / mobile | Sesudah desktop / mobile |
|---|---|---|
| Ringkasan | [1366](../.playwright-mcp/owner-before-summary-1366.png) / [390](../.playwright-mcp/owner-before-summary-390.png) | [1366](../.playwright-mcp/owner-after-summary-1366.png) / [390](../.playwright-mcp/owner-after-summary-390.png) |
| Nilai Final | [1366](../.playwright-mcp/owner-before-nilai-1366.png) / [390](../.playwright-mcp/owner-before-nilai-390.png) | [1366](../.playwright-mcp/owner-after-nilai-1366.png) / [390](../.playwright-mcp/owner-after-nilai-390.png) |
| Kehadiran | [1366](../.playwright-mcp/owner-before-kehadiran-1366.png) / [390](../.playwright-mcp/owner-before-kehadiran-390.png) | [1366](../.playwright-mcp/owner-after-kehadiran-1366.png) / [390](../.playwright-mcp/owner-after-kehadiran-390.png) |
| Rekomendasi | [1366](../.playwright-mcp/owner-before-rekomendasi-1366.png) / [390](../.playwright-mcp/owner-before-rekomendasi-390.png) | [1366](../.playwright-mcp/owner-after-rekomendasi-1366.png) / [390](../.playwright-mcp/owner-after-rekomendasi-390.png) |
| Laporan | [1366](../.playwright-mcp/owner-before-laporan-1366.png) / [390](../.playwright-mcp/owner-before-laporan-390.png) | [1366](../.playwright-mcp/owner-after-laporan-1366.png) / [390](../.playwright-mcp/owner-after-laporan-390.png) |

[Laporan dengan rincian terbuka](../.playwright-mcp/owner-report-open-1366.png). Screenshot halaman utama lain memakai pola `owner-after-{nama}-{390|1366}.png`.

Evidence ignored lokal: `qa-artifacts/owner-targeted/verification.json`, `aggregates.json`, `integrity.json`, `phpunit.log`, `retest.log`, `presentation.log`, `krs-chart.log`, `groq-mocked.log`, `syntax-final.log`, `php-syntax.log`, `pint-final.log`, `build.log`. Artefak QA bukan source untuk di-stage; tidak perlu mempublikasikan data/screenshot pribadi.

## Batas verifikasi

- **PARTIAL font:** file font Plus Jakarta Sans asli tidak dimuat dari internet selama audit; deklarasi/font branding tetap utuh, screenshot memakai fallback. Tidak ada download untuk menutup batas ini.
- **PARTIAL state:** semua kombinasi filter/status/record tidak diuji exhaustif di browser; cakupan berfokus pada halaman terdampak dan invarians backend. Browser selain Chrome Linux yang tersedia tidak diuji langsung.
- Mode KRS multi-kategori diverifikasi di fixture memory; data development saat ini memang hanya satu kategori. Tidak dibuat data baru untuk mempercantik grafik.
- Tidak ada blocker implementasi pada lingkup yang diuji. Tidak ada chart tren/risiko/ekspor baru di luar tiga prioritas yang disepakati.
