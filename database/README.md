# Skema akademik UniNalar

Belum ada berkas ERD pada proyek saat implementasi. Migration domain ini mengikuti entitas yang diminta dan keputusan yang tersedia. Kolom tambahan dapat disesuaikan setelah ERD final direview.

## Level akun

| `users.level` | Role |
| --- | --- |
| 1 | Mahasiswa |
| 2 | Dosen |
| 3 | Admin Akademik |
| 4 | Kepala Program Studi |

`users` menyimpan kredensial akun; `mahasiswa` dan `dosen` menyimpan profil. `user_id` di kedua profil nullable dan unique sehingga data profil dummy dapat dibuat tanpa akun. Password memakai cast `hashed` pada model `User`.

## Relasi inti

- Program studi mempunyai mahasiswa, dosen, dan mata kuliah.
- Mata kuliah ditawarkan sebagai kelas pada semester; kelas dapat memiliki dosen pengampu.
- Mahasiswa mempunyai satu KRS per semester. KRS berisi kelas melalui `krs_detail`; nilai dan kehadiran terkait detail tersebut.
- Analisis risiko disimpan sebagai snapshot bertanggal, sehingga analisis baru tidak menimpa riwayat lama. Setiap snapshot memiliki faktor, rekomendasi, dan tindak lanjut.

Unique constraints mencegah kode/NIM ganda, satu KRS per mahasiswa per semester, duplikasi kelas pada KRS, satu nilai per detail KRS, dan duplikasi faktor dalam snapshot. Penghapusan akun mengosongkan relasi profil/pencatat; penghapusan data induk akademik yang memiliki riwayat dibatasi. Anak dari KRS, analisis, atau rekomendasi dihapus bersama induknya.

## Seeder demo

`UNINLAR_DUMMY_STUDENTS` mengatur jumlah mahasiswa (default 40, rentang 1–100). `DatabaseSeeder` juga menyiapkan satu akun contoh dengan password acak yang di-hash dan tidak dicetak. Seeder tidak dijalankan otomatis oleh Compose. Tinjau dan verifikasi database target sebelum menjalankan `php artisan db:seed` secara eksplisit.
