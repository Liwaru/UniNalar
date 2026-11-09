# Audit UI/UX terarah UniNalar

8 November 2026. **PASS pada cakupan lokal yang diuji.** Audit dilakukan sebelum
perbaikan, bukan mengganti desain karena skill baru tersedia.

## Pedoman dan ruang lingkup

Pedoman utama tetap [standar UI/UX UniNalar](ui-ux-guidelines.md). Skill lokal
[UI/UX Pro Max](../.agents/skills/ui-ux-pro-max/SKILL.md) dipakai sebagai referensi
checklist keyboard/fokus, makna warna, empty state dan ketahanan konten panjang.
Pencarian dataset lokal hanya memakai istilah UI generik, tanpa data mahasiswa.
Rekomendasi native-app, redesign, palet/font baru, dark mode, motion dan framework
baru tidak diterapkan karena tidak sesuai kebutuhan audit existing.

Pencarian yang digunakan: keyboard focus visible web forms (ux), empty states no
content (ux), status color semantic meaning (ux), focus visible button forms dan
select long content overflow (html-tailwind). Hasil empty-state awal terlalu umum;
query dipersempit dan hasil kategori Feedback/Empty States diverifikasi. Panduan
stack diterjemahkan ke Blade/CSS existing, bukan migrasi ke komponen baru. Tidak
ada design system hasil generator yang menggantikan pedoman proyek.

Runtime WSL di http://localhost:8140 dengan SQLite development terisolasi dan
fixture hendrik/jovian/luiz/liwaru. Tidak menggunakan MySQL, source Windows atau
container operasional. Tidak memasang dependency atau mengubah konfigurasi global.

## Temuan sebelum perbaikan

Audit seluruh **60 halaman** pada dua viewport menghasilkan **120 pemeriksaan**:
2 publik, 8 Mahasiswa, 7 Dosen, 27 Admin Akademik dan 16 Owner/Kaprodi. Seluruhnya
HTTP 200, tanpa console exception, ID duplikat, field tanpa label atau referensi
ARIA putus. Temuan nyata:

| Temuan | Bukti sebelum | Perbaikan |
| --- | --- | --- |
| Form tambah/edit kelas melebar pada mobile | Dua kasus 375 px overflow; select mata kuliah dan track label melebar ke 370 px di luar panel | Grid field memakai minmax(0,1fr); kontrol dibatasi width 100% dan min-width 0. Label option utuh tetap tersedia; tidak memotong data atau mengganti select native. |
| Fokus keyboard login terlalu samar | Tombol password fokus tanpa outline, hanya latar biru muda; checkbox/submit memakai ring transparan | Outline solid 3 px dari warna biru existing untuk kontrol login/dialog, dengan offset konsisten. Password toggle, remember dan bantuan tetap berfungsi. |
| Tombol analisis berbeda dari tombol akademik | Border native outset dan latar browser, font/gaya berbeda dari tombol form | Menggunakan aturan tombol form bersama; disabled memakai warna netral, tetap tidak dapat diklik. Live API tidak diaktifkan. |
| Analisis belum diklasifikasi berwarna peringatan | Badge deskriptif null memakai amber yang dapat disalahartikan sebagai tingkat risiko | Variant netral khusus null/unknown pada riwayat dan dashboard mahasiswa. Kategori yang sudah tersimpan tidak diubah. |
| Kotak faktor kosong berlebihan pada hasil deskriptif | Kotak kosong setinggi 150 px, padahal metode memang belum menetapkan bobot | Penjelasan singkat menggantikan kotak/tabel kosong khusus hasil Groq deskriptif tanpa faktor. Faktor existing yang tersedia dan empty state legacy tetap ditampilkan. Owner mendapat penjelasan konsisten. |
| Folder skill memengaruhi build CSS | Build diagnostik memindai contoh skill; CSS dashboard 324,13 kB | Mengecualikan hanya .agents dari source detection Tailwind. CSS final dashboard 298,77 kB; source aplikasi tetap dipindai. |

Pengecualian source detection mengikuti [dokumentasi resmi Tailwind](https://tailwindcss.com/docs/detecting-classes-in-source-files).
Ini mencegah contoh development menghasilkan utility yang tidak dibutuhkan aplikasi;
isi skill, lockfile dan konfigurasi Vite tidak diubah pada sesi ini.

## File yang diubah pada sesi UI ini

- resources/css/admin-academic.css: grid/select form untuk konten panjang.
- resources/css/login.css: fokus keyboard login dan dialog.
- resources/css/student-dashboard.css: tombol AI konsisten, badge netral dan
  pengecualian pemindaian skill.
- resources/css/app.css dan landing.css: pengecualian pemindaian skill saja.
- resources/views/dashboard/mahasiswa/analisis-risiko.blade.php: badge null netral,
  penjelasan faktor deskriptif ringkas.
- resources/views/dashboard/mahasiswa/index.blade.php: variant netral kategori null.
- resources/views/dashboard/owner/risk.blade.php: penjelasan faktor konsisten,
  menghindari tabel kosong khusus hasil deskriptif, bahasa lebih singkat.
- docs/ui-ux-guidelines.md: kedudukan skill sebagai referensi tambahan.
- Dokumen audit ini dan tautan README.

Tidak ada perubahan controller, service bisnis, route, policy, model, migration,
seeder/fixture, autentikasi, penghitungan nilai atau client Groq pada sesi UI ini.
Perubahan sesi sebelumnya tetap dipertahankan. Tidak ada redesign sidebar/navbar,
perubahan palet/font/logo, ikon dekoratif atau fitur tambahan.

## Verifikasi setelah perbaikan

**60 halaman × 5 viewport = 300 pemeriksaan PASS**. Viewport: 375×812, 390×844,
768×1024, 1366×768 dan 1920×1080. Semua HTTP 200, tanpa overflow dokumen, ID
berulang, input visible tanpa label, referensi ARIA putus atau gambar rusak yang
terdeteksi. Tabel lebar tetap memakai region scroll, bukan memaksa seluruh halaman
melebar. Seluruh root/list/detail/create/edit dan halaman empat role yang ada
tercakup dengan parameter dari fixture aktual.

Chrome **156.0.8078.81**, Playwright existing; CDP Network, Runtime dan Performance
aktif untuk kelima konteks role/publik. Tidak ada console/runtime error atau
respons HTTP error pada matriks, dan tidak ada request browser ke api.groq.com.
Tidak memakai MCP browser terpisah atau menginstal browser/package baru.

**22 pemeriksaan interaksi PASS**: fokus keyboard password, reveal/hide,
dialog bantuan dan Escape/fokus kembali, skip link/menu landing mobile, AI tetap
disabled, provenance mock tetap terlihat, badge netral, penjelasan faktor,
select mata kuliah panjang mempertahankan value, form tetap dalam panel,
validasi required native serta Owner tetap baca-saja. Tidak ada POST akademik valid
atau request Groq. Hanya login fixture/session dan GET untuk matriks.

Pengukuran sesudah: outline tombol password solid 3 px; tombol AI tanpa border
native dan memakai latar netral; badge kategori null netral; kotak faktor kosong
150 px tidak lagi dibuat untuk analisis deskriptif. Screenshot sebelum/sesudah
login, risiko Mahasiswa, form kelas mobile, dan Owner diperiksa secara visual.
Screenshot contoh halaman lain juga tersedia, bukan klaim setiap pixel seluruh
halaman diverifikasi manual.

SQLite regression **137 tes / 2.310 assertions PASS**, termasuk workflow KRS/nilai,
role, autentikasi dan mocked Groq. Build **127 modules PASS**. Pint **135 file PASS** dan whitespace Git **PASS**. Tidak ditambahkan tes
yang hanya meniru selector/CSS; perilaku UI diperiksa langsung di browser.

Integritas data: pembacaan read-only membandingkan hash baris **15 tabel akademik**
dengan snapshot development existing sebelum provisioning akun demo: semuanya
sama. Tidak mengubah data akademik, hasil AI atau menulis formula/kategori baru.
Live Groq tetap dinonaktifkan; jumlah request live pada sesi ini **0**.

Bukti lokal ignored di qa-artifacts/ui-ux-pro-max/: before-audit.json (120 kasus),
after-audit.json (300 kasus), before-overflow.json, interactions.json (22 checks),
sqlite-final.log, academic-integrity.json dan screenshot sebelum/sesudah.

## Batas hasil

Ini audit terarah pada data sintetis dan Chrome, bukan sertifikasi seluruh WCAG,
screen reader, browser fisik/Safari/Firefox, beban produksi atau seluruh kemungkinan
panjang data. Fokus yang lebih jelas tidak sama dengan sertifikasi aksesibilitas
menyeluruh. Halaman yang sudah konsisten tidak dirombak. Algoritma klasifikasi
risiko dan keputusan akademik tetap memerlukan spesifikasi terpisah.

Tidak ada perubahan .env, database/volume/container operasional, konfigurasi global,
commit, push atau deployment. Source dan laporan sesi ini belum di-stage; audit Git
ulang diperlukan sebelum commit manual bersama pekerjaan sebelumnya.
