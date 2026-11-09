# Matriks konsistensi desain UniNalar

Inventaris baru berdasarkan 97 route aktual dan pencocokan 60 halaman HTML ke view sumber.
Parameter route diisi ID fixture SQLite terisolasi. GET aliases/redirect, API JSON,
asset dan endpoint mutatif tidak dihitung sebagai halaman HTML tambahan.

Setiap baris: render, DOM, computed styles dan screenshot pada kelima viewport (**R5**).
**G** = render GET; filter utama yang tersedia diuji terpisah (34 halaman).
**M** = workflow mutatif dilindungi regresi SQLite; bukan klaim semua tombol diklik.
Browser CRUD hanya record program studi QA sementara. Owner menggunakan filter GET saja.
Error, validasi, empty, loading dan pagination diuji sebagai state, bukan route tambahan.

**CSS** (`resources/css/`): T=`design-tokens.css`; L=`landing.css`; I=`login.css`;
S=`student-dashboard.css`; A=`admin-academic.css`. Bootstrap diimpor oleh L/S;
Tailwind masuk pipeline Vite existing. `app.css` dibangun/diaudit, tetapi entry
welcome legacy tidak diroute ke 60 halaman ini.

**Warna**: primary #2563eb, teks #14213d; surface putih, dashboard/auth #f8fafc,
landing putih. Status memakai token success/warning/danger dan label teks.
**Font**: Plus Jakarta Sans; body 14px/1,6, judul dan teks tabel memakai hierarki tersendiri.
**B**: kontrak tombol bersama (primary/secondary/danger/disabled/focus sesuai fungsi);
CTA hero 52px dan pagination ringkas 40px adalah variasi fungsional.
**F**: form/GET filter dengan label dan feedback; **T**: tabel pada template,
termasuk data kondisional/alternatif grafik. “—” berarti tidak ada widget tersebut
pada view utama, bukan fitur yang hilang. Komponen dari layout/partial tetap ikut dirender.

Lihat [laporan final](final-design-system-mcp-audit.md) untuk bukti, state, serta batas QA.
View di bawah relatif terhadap `resources/views/` dengan ekstensi `.blade.php`.

| Route | Role | View | CSS | Komponen | Warna | Font | Button | Form | Table | Layout | Responsif | Interaksi |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| / | Publik | landing/index | L+T | public/nav/skip | #2563eb / #14213d | Jakarta 14/1.6 | B | — | — | Publik | R5 PASS | G |
| /dashboard/admin-akademik | admin-akademik | dashboard/admin-akademik/index | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | — | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/dosen | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/dosen/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/dosen/{record} | admin-akademik | dashboard/admin-akademik/resource-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/dosen/{record}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/kelas-kuliah | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/kelas-kuliah/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah} | admin-akademik | dashboard/admin-akademik/kelas-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/krs-pengajuan | admin-akademik | dashboard/admin-akademik/krs-review | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/krs-pengajuan/{history} | admin-akademik | dashboard/admin-akademik/krs-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | — | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mahasiswa | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mahasiswa/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mahasiswa/{record} | admin-akademik | dashboard/admin-akademik/resource-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mahasiswa/{record}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mata-kuliah | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mata-kuliah/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mata-kuliah/{record} | admin-akademik | dashboard/admin-akademik/resource-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/mata-kuliah/{record}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/program-studi | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/program-studi/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/program-studi/{record} | admin-akademik | dashboard/admin-akademik/resource-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/program-studi/{record}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/semester | admin-akademik | dashboard/admin-akademik/resource-index | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/semester/create | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/semester/{record} | admin-akademik | dashboard/admin-akademik/resource-detail | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/admin-akademik/semester/{record}/edit | admin-akademik | dashboard/admin-akademik/resource-form | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/dosen | dosen | dashboard/dosen/index | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/dosen/kelas | dosen | dashboard/dosen/kelas | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/dosen/kelas/{kelas} | dosen | dashboard/dosen/kelas-detail | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | — | — | Akademik | R5 PASS | G + M |
| /dashboard/dosen/kelas/{kelas}/kehadiran | dosen | dashboard/dosen/kehadiran | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/dosen/kelas/{kelas}/peserta | dosen | dashboard/dosen/peserta | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/dosen/nilai | dosen | dashboard/dosen/nilai | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/dosen/profil | dosen | dashboard/dosen/profil | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa | mahasiswa | dashboard/mahasiswa/index | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | — | T | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/analisis-risiko | mahasiswa | dashboard/mahasiswa/analisis-risiko | S+T | dashboard/sidebar/validation/pagination/provenance | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/kehadiran | mahasiswa | dashboard/mahasiswa/kehadiran | S+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/krs | mahasiswa | dashboard/mahasiswa/krs | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/nilai | mahasiswa | dashboard/mahasiswa/nilai | S+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/profil | mahasiswa | dashboard/mahasiswa/profil | S+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/rekomendasi | mahasiswa | dashboard/mahasiswa/rekomendasi | S+T | dashboard/sidebar/validation/pagination/provenance | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G + M |
| /dashboard/mahasiswa/tindak-lanjut | mahasiswa | dashboard/mahasiswa/tindak-lanjut | S+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G + M |
| /dashboard/owner | owner | dashboard/owner/index | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G |
| /dashboard/owner/analisis-risiko | owner | dashboard/owner/risks | S+A+T | dashboard/sidebar/validation/pagination/provenance | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/analisis-risiko/{analysis} | owner | dashboard/owner/risk | S+A+T | dashboard/sidebar/validation/pagination/provenance | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/dosen | owner | dashboard/owner/lecturers | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/dosen/{lecturer} | owner | dashboard/owner/lecturer | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G |
| /dashboard/owner/kehadiran | owner | dashboard/owner/attendance | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/kelas | owner | dashboard/owner/classes | S+A+T | dashboard/sidebar/validation | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Akademik | R5 PASS | G |
| /dashboard/owner/kelas/{classRoom} | owner | dashboard/owner/class | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/krs | owner | dashboard/owner/krs | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/krs/{krs} | owner | dashboard/owner/krs-detail | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/laporan | owner | dashboard/owner/reports | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/mahasiswa | owner | dashboard/owner/students | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/mahasiswa/{student} | owner | dashboard/owner/student | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/nilai | owner | dashboard/owner/grades | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/rekomendasi | owner | dashboard/owner/recommendations | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /dashboard/owner/tindak-lanjut | owner | dashboard/owner/follow-ups | S+A+T | dashboard/sidebar/validation/pagination | #2563eb / #14213d | Jakarta 14/1.6 | B | F | T | Akademik | R5 PASS | G |
| /login | Publik | auth/login | I+T | login/dialog/feedback | #2563eb / #14213d | Jakarta 14/1.6 | B | F | — | Auth | R5 PASS | G |
