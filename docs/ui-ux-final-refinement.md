# Penyempurnaan UI/UX dan verifikasi fungsional UniNalar

8 November 2026. Runtime WSL `http://localhost:8140`; seluruh data pengujian
sintetis. Status akhir dan ringkasan hasil tercatat pada bagian 17.

## 1. Halaman yang diperiksa

Inventaris aktual `artisan route:list --json`: **97 route**, termasuk **85 endpoint
akademik** dan **60 halaman HTML**: 2 publik, 8 Mahasiswa, 7 Dosen, 27 Admin,
16 Owner. [Matriks halaman](ui-ux-page-matrix.md) memetakan route, role, view,
fungsi, pemeriksaan desain, interaksi dan responsivitas. Redirect `/dashboard`,
empat alias `/area/*`, healthcheck dan route storage bertanda tangan bukan halaman
akademik tambahan. POST login/logout dan 27 endpoint akademik mutatif diperiksa
melalui pengujian fungsional yang sesuai; tidak dihitung sebagai halaman visual.

Baseline 137 tes/2.310 assertions, 300 viewport dan 22 interaksi dari laporan
sebelumnya bersifat historis. Angka hasil baru di bawah berasal dari sesi ini.

## 2. Halaman yang diperbaiki

Landing, login, dashboard Mahasiswa, profil Dosen dan shell validasi bersama
Dosen/Admin/Owner mendapat perubahan langsung. Terjemahan validasi Indonesia
bersama melengkapi feedback form seluruh role pada locale `id`. Modul feedback form dipakai oleh
seluruh role. Halaman lain diperiksa; desain yang sudah berfungsi dipertahankan.
Tidak ada perubahan controller, aturan akademik, autentikasi atau integrasi Groq
pada sesi penyempurnaan ini. Perubahan backend lokal dari sesi sebelumnya tetap ada.

## 3. Design system

Pedoman utama: [standar UniNalar](ui-ux-guidelines.md). Skill lokal
[UI/UX Pro Max](../.agents/skills/ui-ux-pro-max/SKILL.md) hanya referensi tambahan.
Pencarian lokal `loading buttons` (ux) dan `time series accessibility` (chart)
memberikan panduan feedback pengiriman dan alternatif tabel grafik. Query chart
awal mengembalikan treemap/bar; dipersempit ke time series sebelum diterapkan.
Tidak mengadopsi palet, font, framework, ambang risiko atau template baru.

`resources/js/form-feedback.js` menangani POST biasa, indikator `aria-busy`,
penonaktifan tombol, pencegahan pengiriman berulang, pemulihan pada `pageshow`, dan
fokus ringkasan error. Method, action, CSRF dan hidden fields tetap dikirim melalui
form Laravel. Pembatalan konfirmasi hapus dihormati. Modul kecil menjadi entry
Vite bersama; halaman non-Mahasiswa tidak memuat Axios/Bootstrap hanya untuk form.
Komponen Blade `dashboard.validation-summary` mengurangi pengulangan markup error.
`lang/id/validation.php` melokalkan pesan aturan umum dan nama field; aturan
validasi, locale environment dan fallback bahasa lain tidak diubah.

## 4. Landing

Sebelum: angka 18,4%, 1.248 mahasiswa, distribusi risiko, bobot contoh, kartu AI
mengambang dan filter semester tanpa fungsi. Label simulasi sudah ada, tetapi
preview masih mudah disalahartikan sebagai kemampuan/angka aktual.

Sesudah: ringkasan kewenangan empat role, alur KRS–persetujuan–nilai final–hasil
studi, serta penjelasan sumber/status analisis. Tidak menampilkan statistik publik
rekaan atau bobot risiko. Navigasi, anchor section, logo, CTA login dan identitas
visual dipertahankan. Tautan footer menjadi “Alur akademik”. Grafik publik beserta
inisialisasi Chart.js yang tidak diperlukan dihapus. 146 aturan CSS ilustrasi lama
yang tidak lagi dipakai dibersihkan. Teks penjelasan diperbesar, warna teks bantuan
diperjelas dan jarak section mobile dipadatkan secara terarah.

Favicon bersama memakai PNG 64×64 (6.388 byte) yang diturunkan dari ikon
UniNalar existing. Ini menggantikan fallback favicon bawaan kosong tanpa memuat
logo sumber 1,14 MB khusus ikon tab. Logo sumber dan favicon kosong lama tetap ada.

## 5. Login

Username/password, remember, password visibility, bantuan akun dan redirect role
tetap tersedia. Tombol password berukuran 44×44 px. Submit menampilkan “Memproses…”
dan status pemrosesan; tombol terkunci selama navigasi. Error server menerima
fokus; autofocus username hanya pada halaman tanpa error. Password tidak diisi
kembali setelah validasi gagal. Tidak ada password demo atau key pada halaman publik.

## 6. Mahasiswa

Delapan halaman beserta KRS/riwayat, nilai final, IPS/IPK, kehadiran, analisis,
rekomendasi, tindak lanjut dan profil diperiksa. Label sumbu/legend grafik meningkat
menjadi 12 px dengan warna teks lebih jelas. Animasi grafik dinonaktifkan bila
pengguna memilih reduced motion. Disclosure “Lihat data nilai per semester”
menyediakan tabel dengan caption dan scope header; nilai rata-rata ditegaskan
sebagai nilai angka, bukan IPS/IPK. Tabel dua kolom ini memakai lebar sesuai
panel agar tidak mewarisi minimum tabel akademik lebar. Disclosure dan lebar tabel
diverifikasi pada kelima viewport. Angka akademik dan hasil mock tidak diubah.

## 7. Dosen

Tujuh halaman diperiksa, termasuk kelas, peserta, kehadiran, penilaian dan profil.
Pesan email yang semula “The email field must be a valid email address.”
kini menjadi “Format Email profil tidak valid.”. Error email/telepon profil terhubung ke input melalui `aria-invalid` dan
`aria-describedby`. Ringkasan error menerima fokus setelah respons validasi.
Tabel nilai lebar tetap memakai scroll region keyboard; status draft/final dan
penguncian nilai final dipertahankan. Nilai final tidak ditulis untuk QA visual.

## 8. Admin Akademik

27 halaman mencakup enam master (daftar/tambah/detail/ubah), dashboard dan review
KRS/riwayat. Form memakai feedback pengiriman bersama dan ringkasan validasi yang
konsisten. Tidak mengubah aturan master atau keputusan KRS. Pengujian CRUD browser
hanya menggunakan satu program QA sementara yang dibuat pada SQLite development,
diperbarui, lalu dihapus melalui UI; data akademik existing tetap dipertahankan.

## 9. Owner/Kaprodi

16 halaman monitoring/detail/laporan diperiksa. Filter tetap GET; tidak ada aksi
mutatif baru. Ringkasan error memakai komponen bersama. Informasi analisis
tersimpan tetap menampilkan asal hasil dan label simulasi, serta tidak memicu AI.

## 10. Accessibility

Pemeriksaan DOM mencakup label kontrol terlihat, ID duplikat, referensi ARIA,
gambar gagal dan overflow. Interaksi keyboard mencakup skip link, fokus password,
Space toggle, dialog bantuan, Tab dan Escape dengan pengembalian fokus. Validasi
Dosen/Admin diuji melalui respons Laravel yang nyata. Alternatif tabel grafik
memiliki caption, header kolom/baris dan region scroll yang dapat difokuskan.
Tidak ada larangan zoom. Reflow lebar 683 px pada landing diperiksa sebagai
padanan layout viewport desktop 1366 px pada zoom 200%; ini bukan sertifikasi zoom
browser atau audit WCAG menyeluruh. Screen reader fisik dan seluruh kombinasi
kontras/state belum diuji. Warna teks baru menggunakan palet existing.

## 11. Hasil responsif

**300 pemeriksaan baru = 60 halaman × 5 viewport**: 375×812, 390×844, 768×1024,
1366×768, 1920×1080. Seluruh respons 200, tanpa overflow dokumen, kontrol terlihat
tanpa label, ID duplikat, referensi ARIA putus atau gambar rusak. Scroll horizontal
yang dibatasi pada tabel akademik tetap tersedia. Screenshot lengkap seluruh
kombinasi tersimpan lokal; contact sheet seluruh 60 halaman desktop ditinjau,
serta contoh mobile login, form kelas, penilaian dan detail analisis diperiksa
pada resolusi yang terbaca. Pemeriksaan ini bukan penilaian manual setiap piksel
pada seluruh 300 screenshot.

Sesudah matriks lengkap, perbaikan terakhir dibatasi pada ikon tab dan lebar
tabel disclosure. Build diulang; favicon diverifikasi kembali pada 60 halaman
dan tabel disclosure pada lima viewport dalam putaran interaksi terakhir.

## 12. Playwright

Chrome existing dijalankan melalui Playwright cached, tanpa instalasi baru.
Hasil DOM/layout dipisahkan dari assertion interaksi. **192 assertion browser** lulus: 127 interaksi/fungsi, 60 referensi favicon
dan 5 pemeriksaan lebar tabel. Di dalam 127 terdapat **34 pemeriksaan form filter GET
utama** pada halaman yang menyediakannya. **9 assertion tambahan error login**
juga lulus. Setelah terjemahan ditambahkan, **8 assertion error Dosen/Admin**
lulus pada respons server nyata (bahasa Indonesia, fokus, tanpa fallback Inggris
dan tanpa overflow). Detail akhir interaksi ada pada bagian 17 dan `qa-artifacts/ui-ux-final/browser-summary.json`.

Login/logout empat akun demo, redirect, regenerasi session, akses dashboard
lintas role, gate mutasi Admin, loading, password toggle, bantuan, navigasi mobile,
disclosure grafik, filter, pagination, error server, empty search dan pembatalan
hapus diperiksa secara interaktif. CRUD program QA serta dua submit berurutan
memastikan hanya satu request pembuatan terkirim. KRS dan finalisasi akademik
mutatif tidak diulang pada catatan final fixture; regresinya memakai SQLite
`:memory:`. Tidak menyamakan keberhasilan GET dengan keberhasilan seluruh aksi.

## 13. Chrome DevTools

Memakai **Chrome DevTools Protocol melalui Playwright**, bukan DevTools MCP atau
klaim pemeriksaan manual panel. Network, Runtime dan Performance diaktifkan.
Matriks final: nol console error/warning, exception, kegagalan request lokal
selain pembatalan navigasi, request browser ke Groq, duplikasi aset per navigasi,
atau pola key Groq pada HTML. Stylesheet termuat di seluruh halaman.
Seluruh **11 file aset** pada manifest final merespons 200 dan tidak mengandung
pola key Groq. Favicon PNG tambahan juga merespons 200. Pemeriksaan pola tidak menggantikan audit semua jenis rahasia.

CLS maksimum yang teramati **0,00135**, diambil setelah font siap dan jeda render
singkat dengan reduced motion; bukan data lapangan/Core Web Vitals produksi.
Putaran awal interaksi mencatat 130 event `net::ERR_ABORTED` bertipe Other.
Trace publik dengan interception mereproduksi pembatalan pada `/favicon.ico`;
file bawaan berukuran nol byte. Referensi ikon diperbaiki di tiga layout, dan
interception pengamatan login dilepas sesudah login. Putaran final 192 assertion browser tidak mencatat pembatalan maupun kegagalan network
lokal (0 event). Error request lainnya tetap diperlakukan sebagai temuan.

## 14. Regresi

- SQLite PHPUnit: **137 tes / 2.317 assertions**, PASS pada source/build final.
- Authentication, authorization, KRS, penilaian, finalisasi, dashboard, Groq mock,
  rendering AI dan reuse tercakup suite existing. Assertion fitur existing tidak
  dilemahkan; kontrak landing ilustratif diganti dengan assertion layanan aktual
  dan larangan statistik/kontrol rekaan setelah preview dihapus.
- HTTP nyata: **7 pemeriksaan**, PASS: CSRF 419, reuse AI 302, identifier mahasiswa
  asing 422, Owner tidak dapat menghasilkan AI 403, nilai final tidak dapat diedit
  422, finalisasi ulang 422, penilaian dosen asing 404. Reuse tidak memanggil provider.
- PHP syntax: **137 file**, PASS. Pint: **136 file**, PASS.
- Syntax JavaScript dan `git diff --check`: PASS. Vite production build: PASS.
- MySQL: NOT RUN; tidak tersedia identitas MySQL QA terisolasi yang diverifikasi.

Artefak terabaikan Git berada di `qa-artifacts/ui-ux-final/`: `routes.json`,
`page-inventory.json`, `before-audit.json`, `after-audit.json`, screenshot
`after-{role}-{page}-{width}.png`, `contact-1.png` s.d. `contact-5.png`,
`browser-summary.json`, `login-errors.json`, `http-summary.json`, `assets.json`,
`integrity.json`, `localization.json`, `phpunit.log`,
`pint.log`, `php-syntax.log` dan `build.log`. Artefak lokal bukan dependency aplikasi.

## 15. Bug/masalah yang diperbaiki

| Temuan | Akar masalah | Perbaikan dan bukti |
| --- | --- | --- |
| Statistik/bobot risiko rekaan dan filter publik tanpa fungsi | Preview statis masih menyerupai dashboard aktif | Diganti informasi layanan/alur; assertion landing dan browser tanpa metrik/kontrol tersebut |
| Form dapat dikirim berulang tanpa feedback | Tidak ada state submit bersama; AI memiliki handler terpisah | Modul POST bersama, loading empat role dan satu request saat submit ganda |
| Pesan validasi masih Inggris pada locale id | Terjemahan Laravel Indonesia belum tersedia | Terjemahan bersama tanpa mengubah rules; 8 assertion error Dosen/Admin PASS |
| Error profil Dosen tidak terhubung ke field | Pesan inline tidak memiliki ID/deskripsi | ARIA field, fokus ringkasan; respons invalid email nyata tanpa penulisan |
| Grafik hanya tersedia melalui canvas dan teks kecil | Tidak ada alternatif angka, label 9–10 px | Tabel disclosure, caption/scope, label 12 px dan reduced motion |
| Favicon fallback kosong | File bawaan nol byte; pembatalan Other muncul saat interception | Favicon 64 px dari ikon existing, tiga layout, asset fetch dan 60 pemeriksaan halaman |
| Target password terlalu kecil | Ukuran tombol 36 px | Ukuran 44 px; keyboard dan pengungkapan tetap berfungsi |
| CSS/JS preview lama tidak diperlukan | Ilustrasi sudah diganti tetapi aturan lamanya tertinggal | Pembersihan 146 aturan CSS dan inisialisasi grafik publik; build/aset diverifikasi |

## 16. Batas dan keamanan

Runtime diverifikasi sebelum QA: bind mount root WSL yang sama, port hanya
127.0.0.1:8140, driver SQLite dan guard `WslDevelopmentIdentity::verify` lulus;
`services.groq.live_enabled=false`. Tidak memakai source Windows atau MySQL
operasional. Tidak mengubah `.env`, APP_KEY, Compose, Docker volume, dependency
atau lockfile. `.env`, SQLite dan artefak QA tetap di-ignore dan tidak ditrack.
Tidak ada commit, staging, push atau deployment. Live Groq: **0 request**.

Nilai akhir dan data akademik lama dilindungi oleh perbandingan hash 15 tabel
akademik sebelum/sesudah QA. Pengujian membuat/menghapus hanya program QA miliknya;
sequence SQLite dapat bertambah, namun baris akademik existing harus tetap sama.
Log authentication/session development dapat berubah sesuai pengujian login.
Hasil mock adalah simulasi, belum validasi layanan Groq live. Formula/kategori
risiko resmi tidak ditambahkan. Chrome saja; Firefox/Safari, screen reader,
perangkat fisik, uji beban dan produksi belum diverifikasi. Pengujian mutatif
seluruh jenis master/KRS/nilai dilakukan suite terisolasi; browser tidak mengklik
setiap mutasi terhadap catatan final yang ada.

## 17. Status akhir

**PASS pada cakupan development lokal yang diuji.**

| Pemeriksaan baru | Hasil |
| --- | --- |
| 60 halaman × 5 viewport | 300/300 PASS |
| Interaksi/fungsi browser empat role, filter dan CRUD QA | 127/127 PASS |
| Referensi favicon seluruh halaman + lebar tabel 5 viewport | 60 + 5 PASS |
| Native validation dan error login | 9/9 PASS |
| Validasi Dosen/Admin berbahasa Indonesia | 8/8 PASS |
| HTTP CSRF, authz, reuse dan proteksi nilai final | 7/7 PASS |
| Regresi SQLite `:memory:` | 137 tes, 2.317 assertions PASS |
| PHP syntax / Pint / JavaScript syntax / Vite / whitespace | PASS |
| 11 aset build + favicon, console, warning dan layout | PASS pada cakupan di atas |
| Hash 15 tabel akademik existing + `.env` | 16/16 identik sebelum/sesudah |
| Live Groq / database operasional / commit atau push | Tidak dijalankan |

Tidak ada blocker pada skenario yang diuji. Status ini bukan persetujuan rilis
produksi atau bukti semua kombinasi aksi/perangkat telah diuji. Halaman yang tidak
memerlukan perbaikan dipertahankan; perbedaan data/otorisasi/aturan akademik tidak
disamarkan melalui perubahan tampilan.
