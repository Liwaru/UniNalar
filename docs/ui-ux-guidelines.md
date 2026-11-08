# Standar UI/UX UniNalar

Standar wajib untuk seluruh pengembangan dan perbaikan halaman: Mahasiswa,
Dosen, Admin Akademik, Owner/Kaprodi, login, dan landing page.

Prioritas: **fungsi → kejelasan → penyempurnaan visual**. Targetnya adalah sistem
informasi akademik yang natural, profesional, matang, dan efisien untuk perguruan
tinggi. Hindari tampilan template AI generik.

## Identitas dan hierarki

- Pertahankan identitas visual, logo, keluarga font, dan palet UniNalar yang ada.
- Gunakan skala tipografi, spacing, ukuran kontrol, dan hierarki judul yang
  konsisten. Teks bantuan dan metadata tetap harus terbaca.
- Gunakan warna untuk fungsi: aksi, navigasi aktif, status, validasi, dan peringatan.
  Status juga harus memiliki label teks agar maknanya tidak bergantung pada warna.
- Hindari gradient, glow, glassmorphism, animasi, dan shadow berlebihan.
- Gunakan card hanya untuk kelompok informasi yang membutuhkan batas visual.
  Bagian biasa dapat memakai judul, jarak, atau pemisah sederhana.
- Ikon harus membantu pengenalan fungsi. Hindari ikon dekoratif berlebihan dan
  emoji pada antarmuka profesional.
- Pertahankan desain existing yang berfungsi; lakukan perbaikan terarah dan hindari
  redesign besar tanpa alasan yang jelas.

## Data akademik dan interaksi

- Tabel harus ringkas dengan header jelas, alignment konsisten, angka mudah
  dibandingkan, dan aksi yang dapat dikenali. Hindari whitespace berlebihan.
- Tabel lebar memakai scroll region yang dapat diakses keyboard; halaman secara
  keseluruhan tetap berada dalam lebar viewport.
- Form memiliki label yang terhubung ke input, penanda kebutuhan yang jelas,
  bantuan singkat bila diperlukan, validasi dekat field, dan feedback keberhasilan
  atau kegagalan. Pertahankan input yang aman setelah validasi gagal.
- Gunakan bahasa Indonesia profesional, singkat, dan spesifik. Hindari kalimat
  pemasaran, jargon implementasi yang tidak membantu pengguna, serta teks generik
  yang terdengar seperti keluaran AI.
- Empty state menjelaskan data yang belum tersedia atau filter yang tidak
  menghasilkan data. Berikan langkah berikutnya hanya jika aksinya tersedia dan
  pengguna berwenang melakukannya.
- Setiap tombol, tautan, filter, atau kontrol harus memiliki fungsi nyata. Jangan
  menambahkan angka, status, notifikasi, atau aksi akademik untuk mengisi ruang.
- Bedakan loading, data kosong, data tidak tersedia, dan kegagalan request.
  Tampilkan loading hanya bila memang ada pemrosesan yang perlu ditunggu.

## Konsistensi dan pemeliharaan

- Sidebar, navbar, breadcrumb, tombol, badge, filter, pagination, dan halaman detail
  mengikuti pola yang sama antarhalaman. Navigasi aktif harus akurat.
- Gunakan layout, komponen Blade, dan partial yang reusable. Simpan aturan visual
  bersama di stylesheet yang sesuai; hindari inline styling dan override acak per
  halaman. Scope variasi role jika memang diperlukan.
- Desktop memiliki kepadatan informasi yang wajar; mobile tetap menyediakan ruang
  untuk membaca dan mengoperasikan kontrol. Hindari konten yang terlalu kosong
  maupun terlalu padat.
- Fokus keyboard harus terlihat, urutan navigasi masuk akal, teks tidak terpotong,
  dan sidebar atau elemen lain tidak menutupi konten.

## Verifikasi setiap halaman yang dibuat atau diubah

1. Bandingkan pola visual dan istilah dengan halaman terkait di role yang sama
   serta komponen bersama.
2. Periksa keterbacaan, hierarki, kepadatan informasi, label, feedback, dan empty/
   error state.
3. Uji kegunaan setiap tombol, tautan, form, filter, serta pagination, termasuk
   validasi, otorisasi, dan dampaknya terhadap halaman terkait.
4. Jalankan build untuk perubahan frontend. Pengujian mutatif tetap memakai QA
   terisolasi sesuai aturan proyek.
5. Jika browser tooling tersedia, periksa tampilan dan interaksi pada 375×812,
   390×844, 768×1024, 1366×768, dan 1920×1080; periksa overflow, tabel, form,
   navigasi mobile, fokus, kontras, console, serta request aset yang gagal.
6. Laporkan bukti secara terpisah: functional/HTTP tests, build, dan visual browser
   QA. Bila browser tidak tersedia, nyatakan verifikasi visual BLOCKED atau NOT RUN;
   keberhasilan build/HTTP tidak membuktikan tampilan telah diverifikasi visual.

Dokumen ini menetapkan standar untuk pekerjaan berikutnya. Pencatatannya sendiri
tidak menyatakan seluruh halaman existing telah diaudit ulang atau memenuhi semua
kriteria visual.
