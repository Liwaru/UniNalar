# Docker lokal UniNalar

Konfigurasi ini terpisah dari `.env` lokal dan memakai `compose.local.yaml`. Source code di-mount ke PHP-FPM; berkas SQLite lokal dipasang read-only sebagai perlindungan tambahan. Nginx tersedia di http://localhost:8080, dan MySQL hanya dapat diakses dari host lokal pada port 3307.

## Memulai

```powershell
Copy-Item .env.docker.example .env.docker
# Isi APP_KEY, DB_PASSWORD, MYSQL_PASSWORD, dan MYSQL_ROOT_PASSWORD
docker compose -f compose.local.yaml build
docker compose -f compose.local.yaml up -d
docker compose -f compose.local.yaml ps
```

Laravel memerlukan `APP_KEY`. Buat key khusus Docker (misalnya `php artisan key:generate --show`) dan atur kredensial database unik di `.env.docker` (file ini diabaikan Git). Jangan menyalin key production ke sini. Jangan mengubah `.env` yang dipakai workflow host.

Sebelum `up`, periksa `docker volume ls` dan `docker compose -f compose.local.yaml config --volumes`. Volume `uninlar_mysql_data` akan dipakai kembali jika sudah ada (Compose biasanya memberi prefix nama proyek, misalnya `uninlar_`). Periksa database di volume itu terlebih dahulu; jangan menghapus volume. Kredensial variabel `MYSQL_*` hanya dipakai inisialisasi MySQL saat volume baru, dan tidak mengganti kredensial database pada volume lama.

phpMyAdmin tersedia di http://localhost:8081. Periksa endpoint aplikasi dan koneksi database tanpa migrasi:

```powershell
docker compose -f compose.local.yaml exec app php artisan db:show
Invoke-WebRequest http://localhost:8080/up
```

`db:show` hanya membaca metadata. Tidak ada migrasi otomatis.

Jangan jalankan migration sebelum memeriksa bahwa Compose menunjuk ke database/volume development yang benar dan memastikan backup tersedia. Setelah target diverifikasi dan migration direview, terapkan dengan `docker compose -f compose.local.yaml exec app php artisan migrate`. Perintah ini tidak dijalankan oleh startup container.

Seeder demo dapat dikendalikan dengan `UNINLAR_DUMMY_STUDENTS` (default 40, dibatasi 1–100). Setelah migration ditinjau dan database target diverifikasi, jalankan secara eksplisit `docker compose -f compose.local.yaml exec app php artisan db:seed`. Seed ini membuat profil mahasiswa tanpa akun login dan contoh riwayat akademik/risiko. Perintah seeding menulis data, jadi jangan jalankan pada database yang berisi data penting.

## Backup dan pemulihan MySQL lokal

Backup SQL:

```powershell
docker compose -f compose.local.yaml exec -T db sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > uninlar-local.sql
```

Pemulihan menimpa tabel bernama sama. Pastikan target benar dan buat backup sebelum mengimpor:

```powershell
Get-Content .\uninlar-local.sql | docker compose -f compose.local.yaml exec -T db sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

Jangan menjalankan `down -v`; itu akan menghapus volume database.
