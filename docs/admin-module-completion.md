# Penyelesaian modul Admin Akademik

Implementasi fungsional dan QA otomatis selesai untuk ruang lingkup di bawah.
Penerimaan visual/responsif masih **BLOCKED** karena tooling browser tidak tersedia.
Laporan ini tidak menyatakan aplikasi 100% selesai atau siap produksi.

## 1. Workspace dan Git

Workspace `/home/hendra_huang/projects/UniNalar-main`, branch `main`, HEAD
`02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`. Perubahan Mahasiswa/Dosen yang sudah
ada dipertahankan. Tidak ada commit, push, deployment, perubahan `.env`/Compose,
migration database operasional, atau penghapusan arsip lama. Dependency lokal
digunakan tanpa unduhan. Lockfile Composer/npm tidak diubah.

## 2. Halaman Admin yang tersedia

Dashboard; daftar, tambah, detail, edit untuk Program Studi, Semester, Mata
Kuliah, Dosen, Mahasiswa, dan Kelas Kuliah; daftar pengajuan KRS serta detail
versi pengajuan. Hapus dilakukan melalui form pada daftar dengan proteksi relasi.
Semua menggunakan middleware autentikasi dan role Admin Akademik (level 3).

## 3. Halaman baru

Detail lima master generik dan detail versi pengajuan KRS merupakan enam endpoint
GET tambahan. Dashboard menggunakan controller dan data database aktual.
Detail mahasiswa memuat KRS; detail pengajuan menghubungkan versi sebelumnya.

## 4. Halaman diperbaiki

Daftar/form enam master, dashboard, review KRS, detail kelas, layout Admin,
breadcrumb, pesan kesalahan, dan navigasi detail diperbaiki. Detail kelas memuat
peserta berpaginasi serta membedakan KRS menunggu dan disetujui.

## 5. CRUD per modul

| Master | Tambah/baca/ubah/hapus | Proteksi utama |
| --- | --- | --- |
| Program Studi | Tersedia | Kode unik; relasi mencegah hapus |
| Semester | Tersedia | Kode unik, format tahun, jendela KRS, satu semester aktif |
| Mata Kuliah | Tersedia | Kode unik, program valid, SKS historis dilindungi |
| Dosen | Tersedia | NIDN unik, akun role Dosen, relasi kelas |
| Mahasiswa | Tersedia | NIM unik, akun role Mahasiswa, program/status valid |
| Kelas Kuliah | Tersedia | Kode unik, relasi valid, kapasitas dan identitas terlindungi |

Akun ditautkan dari akun yang sudah tersedia. Tidak ada lifecycle akun baru
atau kebijakan akademik tambahan yang dibuat.

## 6. Dashboard

Enam jumlah master memakai scope operasional yang sama dengan halaman daftar.
Status KRS dihitung dari kondisi KRS saat ini, bukan jumlah seluruh versi riwayat.
Data demo dikecualikan. Database kosong menampilkan nol; schema belum tersedia
menampilkan keadaan terkontrol, bukan angka contoh.

## 7. Workflow KRS

Daftar default menampilkan versi terbaru yang menunggu. Filter seluruh versi
menyediakan riwayat lengkap. Detail menampilkan snapshot tersimpan, mahasiswa,
semester, pengirim, pengambil keputusan, waktu WIB, dan catatan. Persetujuan atau
penolakan hanya tersedia bagi versi terbaru yang masih menunggu pada KRS yang
masih menunggu. Service memvalidasi ulang di dalam transaksi; versi lama,
keputusan ganda, dan keputusan kedaluwarsa ditolak. Snapshot lama tetap utuh
setelah perubahan nama mata kuliah dan pengajuan ulang.

## 8. Integrasi lintas role

QA mencakup kelas buatan Admin yang dibaca Dosen, pengajuan Mahasiswa,
penolakan/pengajuan ulang/persetujuan Admin, roster resmi Dosen, nilai
draft/revisi/final, ringkasan Mahasiswa, dan halaman Owner. Regresi Mahasiswa
dan Dosen tetap dijalankan bersama tes baru.

## 9. Search, filter, pagination

Pencarian master memakai parameter query; teks `0` kini diproses. Filter tersedia
sesuai master: program, semester, dosen, mata kuliah, aktif/nonaktif, atau status
mahasiswa. Review KRS memiliki pencarian mahasiswa, program, semester, status,
dan cakupan versi. Pagination mempertahankan query string. Daftar master memakai 15 baris dan review KRS 20 baris. KRS pada detail
mahasiswa serta versi pada detail pengajuan memakai 15 baris; peserta kelas
20 baris. Relasi yang dirender dimuat melalui eager
loading/count; ini bukan benchmark beban produksi.

## 10. Validation

Validasi server mencakup keunikan, tipe dan panjang input, relasi/role akun,
penanda data demo, tanggal, kapasitas, serta aturan akademik existing. Pesan
Admin berbahasa Indonesia. Form menyediakan label, bantuan, maxlength,
required, error per field, dan feedback global. Konflik unique/FK setelah
validasi menjadi pesan aman tanpa SQL internal.

## 11. Authorization

Tes mencakup guest, role lain, ID tidak valid, dan data demo pada detail baru.
Scope operasional diterapkan sebelum mengambil record. Form mutasi menggunakan
CSRF; HTTP QA berjalan dengan CSRF aktif. Blade meng-escape metadata/catatan;
uji payload HTML dan pencarian menyerupai SQL lulus.

## 12. Database integrity

Hapus master mengunci record dan memeriksa relasi dalam transaksi, dengan
constraint database sebagai lapisan tambahan. Perubahan identitas/dosen kelas
yang memiliki KRS ditolak server; form juga menjelaskan penguncian. Kapasitas
tidak boleh di bawah reservasi. Perlindungan SKS historis dipertahankan.
Tidak ada migration atau model baru dalam pekerjaan Admin ini.

## 13. Concurrency

Sembilan skenario dua proses/koneksi MySQL lulus: aktivasi semester bersaing;
penurunan kapasitas melawan submit; submit ganda; kursi terakhir; keputusan
bersaing; pembuatan nilai ganda; finalisasi melawan edit; revisi kedaluwarsa;
hapus kelas yang memiliki KRS. Dua skenario pertama ditambahkan pada pekerjaan
Admin. Penguncian semester menserialkan aturan satu semester aktif yang sudah
ada. Hasil tidak membuktikan seluruh kemungkinan interleaving produksi.

## 14. UI/UX natural

Mengikuti `ui-ux-guidelines.md`, identitas dan layout UniNalar dipertahankan.
CSS Admin reusable menyediakan tabel ringkas, grid informasi, filter, form,
badge status, focus outline, dan feedback. Partial filter/keputusan dipakai ulang.
Tidak ditambahkan gradient, glow, glassmorphism, animasi dekoratif, atau ikon
tanpa fungsi. Bahasa antarmuka singkat dan profesional.

## 15. Responsive readiness

CSS mengatur grid responsif dan area tabel yang dapat digulir/fokus. Pemeriksaan
kode dan HTTP dilakukan; keterbacaan, kontras, keyboard, sidebar, dan overflow
pada viewport nyata belum diverifikasi secara visual. Target 375×812, 390×844,
768×1024, 1366×768, dan 1920×1080 seluruhnya menunggu browser QA.

## 16. SQLite

PASS: **107 tes, 1.501 assertions** dengan SQLite `:memory:` khusus testing.
Termasuk 11 tes Admin baru dan regresi lintas role. Assertion teks pengantar
dashboard lama diperbarui menjadi assertion judul/data jumlah nol untuk akun
demo; proteksi demo tidak dihapus.

## 17. MySQL

PASS: **47 tes, 639 assertions**, termasuk sembilan skenario concurrency.
MySQL 8.4 QA dibuat sementara pada network internal tanpa published port dan
tanpa volume data persisten. Manifest diverifikasi terhadap identitas server
aktual, schema `qa_` unik, serta marker QA sebelum migration. Database operasional
tidak diakses. Harness membersihkan container/network dan kredensial sementara.

## 18. HTTP

PASS: **205 pemeriksaan** memakai HTTP nyata, cookie/session, CSRF aktif, SQLite
sementara baru, serta server localhost dalam container QA. Cakupan meliputi CRUD
enam master, detail, duplikasi, hapus berelasi, workflow KRS lintas role, nilai,
profil, akses terlarang, dan asset build. Persistensi akhir diperiksa kembali
terhadap file SQLite QA yang identitasnya diverifikasi. Ini bukan browser visual.

## 19. PHP, Pint, Vite

PHP 8.2.34 dari runtime Docker lokal digunakan karena PHP host tidak tersedia.
Pemeriksaan sintaks PHP PASS untuk 111 file; Laravel Pint test mode PASS untuk 112 file. Vite build PASS (127
modules); ada dua warning optimasi CSS `Unknown at rule: @charset` dari stylesheet
yang telah ada. Build tetap berhasil; tidak diklaim bebas warning.

## 20. Browser QA

**BLOCKED**: Playwright MCP dan Chrome DevTools MCP tidak tersedia pada sesi ini.
Tidak ada instalasi tooling browser. Screenshot, console browser, layout lima
viewport, kontras, dan interaksi keyboard tidak dinyatakan PASS. HTTP/asset200
tidak menggantikan pengujian visual.

## 21. Bug yang diperbaiki

Dashboard statis; detail master/KRS tidak tersedia; kurangnya filter/riwayat
pengajuan; pencarian `0` terabaikan; konflik constraint setelah validasi belum
menjadi feedback aman; aktivasi semester bersaing belum diserialkan; detail
kelas belum menyediakan roster berpaginasi; serta bantuan/feedback/field terkunci
yang belum konsisten. Regression tests memeriksa perilaku tersebut.

## 22. Blocker tersisa

Penerimaan visual/responsif membutuhkan browser QA. Deployment, migration
operasional, backup/restore data nyata, uji beban produksi, dan penilaian keamanan
menyeluruh berada di luar bukti QA ini. Tidak ada sertifikasi siap produksi.

## 23. Memerlukan spesifikasi

Koreksi SKS historis, pembukaan nilai final, lifecycle akun, aturan akademik baru,
dan format ekspor/laporan membutuhkan keputusan resmi sebelum diimplementasikan.
Alur yang tidak didefinisikan tidak dibuat berdasarkan asumsi.

## 24. Verdict akhir

**Fungsional dan automated QA PASS untuk ruang lingkup Admin yang terdefinisi;
penerimaan akhir PARTIAL karena browser/responsive QA BLOCKED.** Ini merupakan
implementasi aplikasi, bukan hanya penambahan dokumentasi UI/UX. Bukti lokal
tersimpan di `qa-artifacts/admin/` (diabaikan Git): log PHPUnit, MySQL/manifest,
HTTP checks, build, syntax, dan Pint. Laporan modul lain adalah snapshot historis;
angka pada laporan ini merupakan run gabungan terbaru. Container PHP/HTTP QA
sudah dihapus; container operasional tetap berjalan. `git diff --check` PASS.
