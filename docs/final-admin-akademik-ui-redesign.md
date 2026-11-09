# Targeted Admin Akademik UI/UX redesign

Tanggal: 9 November 2026. Branch `main`, baseline `fe5f206`.

**FIXED:** redesign terarah delapan halaman Admin Akademik selesai. **PASS:** cakupan pengujian lokal yang dirinci di bawah. **PARTIAL:** font asli selama pengujian offline dan kombinasi state yang tidak diuji secara exhaustif. Tidak ada klaim seluruh aplikasi/production sudah diverifikasi.

## Audit awal dan masalah visual

Workspace dan checkpoint sesuai permintaan. Source awal bersih; hanya `.agents/` yang sudah untracked dan tidak disentuh. Komponen dialog, filter-reset, validasi, pagination dan tokens hasil pekerjaan sebelumnya dipertahankan.

Satu sketsa konsep tersedia pada pesan terbaru. Delapan screenshot referensi terpisah tidak tersedia pada pesan tersebut; audit karena itu memakai aplikasi lokal secara langsung. Screenshot sebelum perubahan diambil dari dashboard ringkasan, Mahasiswa, Semester, Kelas Kuliah dan Pengajuan KRS pada desktop/mobile.

Temuan aktual:

- Tombol Tambah berada pada baris tersendiri di atas filter, terpisah dari judul.
- Tombol Cari berdiri sendiri di kiri setelah baris kontrol; jumlah hasil tidak terlihat dekat filter.
- Aksi Detail/Ubah/Hapus mempunyai bobot border hampir sama pada setiap baris.
- Status mahasiswa masih tampil `aktif`; username dan status perlu tetap jelas sebagai informasi berbeda.
- Tanggal membutuhkan whitespace khusus; periode KRS masih tampil sebagai string timestamp mentah.
- Navigasi cepat ringkasan berupa deretan link; statistik kurang terstruktur secara visual.

## Halaman yang diperbaiki

Semua route berikut diaudit menggunakan Playwright MCP pada 375×812, 390×844, 768×1024, 1366×768 dan 1920×1080: **8 halaman × 5 viewport = 40 pemeriksaan layout**.

| Halaman | Route | Perubahan utama |
|---|---|---|
| Ringkasan Akademik | `/dashboard/admin-akademik` | Hierarki angka, pembeda status KRS, grid navigasi cepat |
| Program Studi | `/dashboard/admin-akademik/program-studi` | Header, toolbar, jumlah hasil, aksi inline yang lebih tenang |
| Semester | `/dashboard/admin-akademik/semester` | Toolbar, tanggal nowrap, format KRS berlabel WIB, aksi hybrid |
| Mata Kuliah | `/dashboard/admin-akademik/mata-kuliah` | Toolbar, nama lebih tegas, aksi inline |
| Kelas Kuliah | `/dashboard/admin-akademik/kelas-kuliah` | Filter dua baris terencana, nama/kode terbaca, aksi hybrid |
| Pengajuan KRS | `/dashboard/admin-akademik/krs-pengajuan` | Toolbar/jumlah sesuai cakupan, waktu nowrap, penjelasan workflow |
| Dosen | `/dashboard/admin-akademik/dosen` | Toolbar, identitas, badge status, aksi hybrid |
| Mahasiswa | `/dashboard/admin-akademik/mahasiswa` | Toolbar, identitas, badge status, aksi hybrid |

## Keputusan desain dan komponen existing

### Header dan filter

Judul/deskripsi tetap pada header utama. Tombol Tambah dipindahkan sejajar ke header melalui section opsional `page-actions`, bukan menambahkan card. Label pencarian disesuaikan dengan jenis data. Input tetap submit GET; tidak ada pencarian otomatis setiap ketikan atau perubahan semantik pencarian backend.

Toolbar memakai latar token background lembut, tanpa card bertumpuk. Jumlah hasil dari paginator `total()` berada satu baris dengan kelompok Hapus filter/Terapkan. Total mengikuti query filter, bukan jumlah baris halaman aktif. Reset menggunakan **`x-filter-reset` existing**, disabled secara semantik pada daftar master tanpa filter aktif. Pengajuan KRS tetap reset ke default existing: Menunggu dan Versi terbaru per KRS.

Filter pendek dua kolom; filter panjang tiga kolom desktop, dua kolom tablet, satu kolom mobile. Lima filter Kelas Kuliah tersusun 3+2 pada desktop; footer hasil/aksi mempunyai baris penuh. Tinggi kontrol existing dipertahankan, dengan target tombol setidaknya 44 px. Tidak ada filter penting yang disembunyikan.

### Aksi baris

- Program Studi dan Mata Kuliah: **inline**, karena tabel lebih sederhana. Detail mempunyai border netral; Ubah dan Hapus lebih tenang, tetap berlabel teks dan danger untuk Hapus.
- Semester, Kelas Kuliah, Dosen dan Mahasiswa: **hybrid**. Detail tetap langsung; Ubah/Hapus berada pada popover tiga titik berlabel aksesibel `Aksi untuk …`.
- Pengajuan KRS mempertahankan Detail dan keputusan kontekstual existing. Tidak mengganti workflow dengan menu master CRUD.

Komponen baru `x-admin.record-actions` menggabungkan markup aksi master yang sebelumnya berada langsung pada view indeks. Ia memakai route, POST, CSRF, method DELETE dan **`x-confirm-action` existing**. Memilih Hapus hanya membuka dialog UniNalar; tidak menghapus langsung.

Popover native tampil pada top layer agar tidak terpotong container tabel. Tombol mempunyai `aria-controls`, `aria-expanded`, nama aksesibel, fokus terlihat, dan dukungan Enter/Tab/Escape. Ini disclosure berisi link/tombol biasa, bukan ARIA menu yang menuntut model keyboard berbeda. Posisi mengikuti anchor ketika halaman/tabel bergeser. Setelah dialog dibatalkan, fokus kembali ke tombol aksi baris.

Tanpa dukungan Popover, Ubah/Hapus tetap inline. Tanpa JS, tombol menu disembunyikan, link Ubah tetap terlihat, dan penghapusan tetap disabled/inert sesuai fallback aman dialog existing. Tidak ada native confirm/alert/prompt baru.

### Tabel, tanggal, dan ringkasan

Nama menjadi lebih tegas; kode/NIM/NIDN/nama dosen tertentu tidak dipecah per huruf. Nama mata kuliah boleh wrap pada batas kata. Tanggal memakai nowrap; tanggal biasa `dd/mm/yyyy`, periode KRS `dd/mm/yyyy HH:mm` dengan label WIB. Perubahan hanya presentasi, tanpa konversi atau perubahan data tersimpan. Username tetap kolom Username akun; status aktif menjadi badge **Aktif**, bukan mengubah username menjadi indikator status.

Tabel mempertahankan semua kolom, divider halus, hover ringan, dan scroll horizontal lokal dengan container focusable. Minimum lebar tabel 760 px; tabel padat dapat lebih lebar sesuai isi. Tidak ada overflow horizontal seluruh dokumen pada matriks.

Ringkasan mempertahankan angka database dan catatan demonstrasi sintetis. Angka memiliki ritme label/nilai yang jelas; status KRS dibedakan dengan garis warna bermakna dan label teks. Navigasi cepat menjadi grid link berukuran nyaman tanpa chart dekoratif atau angka baru.

## File berubah

| File | Peran |
|---|---|
| `resources/css/admin-academic.css` | CSS scoped management/ringkasan, toolbar, tabel, status dan popover |
| `resources/js/admin-actions.js` | Baru; progressive enhancement popover dan pemulihan fokus |
| `resources/js/form-feedback.js` | Import modul aksi Admin; feedback existing tetap |
| `resources/views/components/admin/record-actions.blade.php` | Baru; pola aksi inline/hybrid |
| `resources/views/dashboard/admin-akademik/resource-index.blade.php` | Header, toolbar, total, presentasi nilai dan komponen aksi |
| `resources/views/dashboard/admin-akademik/krs-review.blade.php` | Toolbar/cakupan hasil dan keterbacaan pengajuan |
| `resources/views/dashboard/admin-akademik/index.blade.php` | Hierarki ringkasan dan navigasi cepat |
| `resources/views/layouts/dashboard.blade.php` | Slot opsional aksi header, satu baris |
| `tests/Feature/AdminManagementPresentationTest.php` | Baru; total filter/pagination, tanggal dan status tanpa mutasi nilai |
| `docs/final-admin-akademik-ui-redesign.md` | Laporan ini |

Tidak mengubah controller, route, middleware, policy, model, schema atau layanan bisnis. Styles baru memakai kelas khusus management/ringkasan Admin; Owner yang ikut memuat stylesheet tidak memperoleh redesign tersebut.

## Playwright MCP — PASS pada cakupan teruji

Tool asli: **`mcp__playwright__browser_run_code_unsafe`**. Browser/context berasal dari MCP; tidak menggunakan CLI atau CDP langsung sebagai pengganti.

| Verifikasi | Bukti hasil |
|---|---|
| Delapan halaman, lima viewport | 40 render HTTP 200; nol overflow dokumen, nol JS error, nol asset failure pada matriks akhir |
| Toolbar | Semua field berada sebelum footer; tombol terlihat dan target minimal 44 px |
| Enam master | Pencarian, retained value, reset, empty state dan total nol PASS |
| Navigasi enam master | Tambah, Detail, Ubah menuju halaman yang benar |
| Keyboard empat tabel hybrid | Enter membuka, Tab menuju Ubah, Escape menutup/mengembalikan fokus |
| Pengajuan KRS | Status Semua + cakupan Semua versi dipertahankan; hasil aktual tiga pengajuan; reset ke default PASS |
| Pagination | Kelas dengan query `q=A`: halaman 2 tetap membawa query, total tetap 16 |
| Modal hapus fixture | Enam resource × lima viewport: 30 pemeriksaan fokus/layout PASS |
| Batal/Escape | Nol request mutatif; fokus kembali ke pemicu |
| Double-submit | Masing-masing fixture menghasilkan tepat satu request terintersepsi, CSRF dan DELETE tetap |
| Feedback hapus | HTML hasil Laravel fixture menampilkan pesan sukses; bukan pesan sukses buatan JS |
| Fallback tanpa JS | Toggle tersembunyi, Ubah terlihat, Hapus disabled/inert |
| Tanpa dukungan Popover | Aksi inline terlihat; konfirmasi tetap dapat diinisialisasi |
| Role lain | Nilai Mahasiswa, Nilai Dosen, Kelas Owner pada 390/1366: HTTP 200, tidak overflow/error, tidak ada kontrol Admin baru |

Pengujian hapus browser memakai HTML Laravel dari fixture SQLite memory dengan intersepsi request. **Tidak ada request penghapusan yang diteruskan ke backend development.** Backend create/update/delete sebenarnya diuji dalam SQLite memory pada generator fixture. Ini bukan klaim browser menghapus data database development secara end-to-end.

Pada iterasi awal ditemukan CSS display yang membuat popover tertutup masih terlihat, serta menu yang tertutup akibat event scroll ketika tombol dibawa ke viewport. Keduanya diperbaiki dan pemeriksaan terdampak diulang. Harness pagination awal memakai query yang hanya menghasilkan satu halaman; diganti query yang benar-benar menghasilkan dua halaman. Hasil akhir di atas berasal dari run yang selesai.

## Chrome DevTools MCP — PASS pada cakupan teruji

Tool asli yang dipanggil: **`mcp__chrome_devtools__new_page`**, **`evaluate_script`**, **`emulate`**, **`list_console_messages`**, **`list_network_requests`**, **`close_page`** (semua pada namespace `mcp__chrome_devtools__`).

| Computed/observasi | Hasil |
|---|---|
| Kelas desktop 1366 | Grid 322/322/322 px, gap 14×16 px |
| Kelas mobile 390 | Satu kolom 326 px; menu 160 px berada pada x197–357, tetap dalam viewport |
| Scroll tabel | `overflow-x: auto`; desktop tabel 1022 px dalam container 1038 px; mobile tabel ±947 px dalam container 358 px |
| Target menu | Tinggi ±51,6 px, melampaui minimum 44 px |
| Semester | Tanggal `09/02/2026`, `19/06/2026` nowrap; lebar sel ±99 px |
| Warna/font | Primary `#2563eb`, heading navy `rgb(20, 33, 61)`; family Plus Jakarta Sans + fallback existing |
| Console | Tidak ada error/warning pada pemeriksaan akhir |
| Network | Asset lokal HTTP 200; redirect login 302 normal; tidak ada request eksternal |

Agar benar-benar offline, HTML server lokal diambil melalui browser lalu link eksternal dikeluarkan sebelum render dengan CSP lokal. JS/CSS aplikasi tetap dimuat dari localhost. Playwright juga mengintersepsi origin eksternal tanpa meneruskan request. Tidak ada download atau instalasi. **PARTIAL font:** glyph font asli Plus Jakarta Sans tidak diuji karena tidak dimuat dari eksternal; screenshot menggunakan fallback. Branding/font declaration aplikasi tidak diubah.

## Regression, build, dan data

- Suite terarah: `AdminManagementPresentationTest`, `AdminAcademicManagementTest`, `AdminFormStateTest`, `AdminModuleTest`, `AcademicUiComponentsTest` — **26 skenario unik akhirnya lulus**. Run awal: 25 lulus, satu gagal karena empty state lama. Source diperbaiki untuk membedakan daftar kosong dari pencarian tanpa hasil, tanpa melemahkan assertion. Rerun hanya test gagal dan dua tes presentasi terdampak: **3 tests/138 assertions PASS**. Tes lain yang sudah lulus tidak diulang.
- Generator fixture CRUD enam master: **1 test/124 assertions PASS**, termasuk penghapusan dengan perlindungan relasi.
- KRS terarah: pengajuan, snapshot, tolak/ajukan ulang/setujui dan authorization halaman: **2 tests/24 assertions PASS**.
- Lima Blade berubah dikompilasi dan PHP syntax diperiksa; tes PHP baru syntax PASS.
- Laravel Pint pada tes baru PASS; `node --check` modul aksi PASS.
- Vite build final PASS, **3,96 detik**; hanya memakai dependency terpasang. Build diulang saat source JS/CSS berubah, bukan mengulang pengujian global tanpa alasan.
- `git diff --check` PASS; staging kosong. Tidak ada Git add/commit/push/merge/reset/clean/deployment.

Database aktif diverifikasi SQLite development terisolasi, bukan MySQL operasional. Fingerprint read-only sebelum/sesudah menunjukkan **24/24 tabel identik**, termasuk akun, data akademik, KRS, nilai, analisis dan rekomendasi. Foreign-key check tidak menemukan error. **19/19 file terlindungi identik**, mencakup `.env`, konfigurasi layanan, routes, controllers dan ledger Groq. Tidak ada API key/credential dicetak dalam laporan. Data Hendrik/Jovian, fixture utama dan hasil Groq tetap utuh.

Tidak ada perubahan Docker, MCP, APP_KEY, source Windows, workflow KRS atau perhitungan nilai. Tidak ada request Groq live.

## Screenshot sebelum/sesudah dan artefak

| Halaman | Sebelum desktop / mobile | Sesudah desktop / mobile |
|---|---|---|
| Ringkasan | [1366](../.playwright-mcp/admin-target-before-summary-1366.png) / [390](../.playwright-mcp/admin-target-before-summary-390.png) | [1366](../.playwright-mcp/admin-target-after-summary-1366.png) / [390](../.playwright-mcp/admin-target-after-summary-390.png) |
| Mahasiswa | [1366](../.playwright-mcp/admin-target-before-mahasiswa-1366.png) / [390](../.playwright-mcp/admin-target-before-mahasiswa-390.png) | [1366](../.playwright-mcp/admin-target-after-mahasiswa-1366.png) / [390](../.playwright-mcp/admin-target-after-mahasiswa-390.png) |
| Semester | [1366](../.playwright-mcp/admin-target-before-semester-1366.png) / [390](../.playwright-mcp/admin-target-before-semester-390.png) | [1366](../.playwright-mcp/admin-target-after-semester-1366.png) / [390](../.playwright-mcp/admin-target-after-semester-390.png) |
| Kelas Kuliah | [1366](../.playwright-mcp/admin-target-before-kelas-kuliah-1366.png) / [390](../.playwright-mcp/admin-target-before-kelas-kuliah-390.png) | [1366](../.playwright-mcp/admin-target-after-kelas-kuliah-1366.png) / [390](../.playwright-mcp/admin-target-after-kelas-kuliah-390.png) |
| Pengajuan KRS | [1366](../.playwright-mcp/admin-target-before-krs-pengajuan-1366.png) / [390](../.playwright-mcp/admin-target-before-krs-pengajuan-390.png) | [1366](../.playwright-mcp/admin-target-after-krs-pengajuan-1366.png) / [390](../.playwright-mcp/admin-target-after-krs-pengajuan-390.png) |

Menu aksi: [desktop](../.playwright-mcp/admin-target-actions-1366.png), [mobile](../.playwright-mcp/admin-target-actions-390.png). Dialog: [desktop](../.playwright-mcp/admin-target-dialog-1366.png), [mobile](../.playwright-mcp/admin-target-dialog-390.png). Screenshot dialog record utama hanya membuka/membatalkan; tidak ada submit.

Evidence lokal ignored: `qa-artifacts/admin-targeted/verification.json`, `integrity.json`, `phpunit.log`, `phpunit-retest.log`, `fixtures.log`, `krs.log`, `syntax.log`, `pint.log`, `build.log`. Fixture/artefak browser dapat memuat data QA dan token sesi test; jangan dipublikasikan atau di-stage sebagai source. `.agents/` tidak berubah dan tidak termasuk implementasi ini.

## Batas dan masalah tersisa

Tidak ada blocker implementasi pada cakupan teruji. Font asli offline: NOT VERIFIED. Semua kombinasi pagination/filter pada semua record dan seluruh state keputusan KRS di browser: PARTIAL; pengujian menargetkan alur yang terdampak, dengan coverage backend terisolasi untuk keamanan workflow. Tabel padat tetap perlu scroll lokal pada layar kecil agar informasi penting tidak dihilangkan. Dukungan browser di luar Chrome Linux yang tersedia tidak diuji langsung; fallback tanpa Popover diuji dengan menonaktifkan capability tersebut.
