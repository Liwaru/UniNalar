# UniNalar — checklist setelah hosting tersedia

Seluruh item di bawah **PENDING HOSTING** sampai ada bukti dari server/domain aktual. Tidak ada URL publik yang telah diuji pada tahap persiapan lokal.

## Sebelum upload dan mutation

- [ ] Provider/paket, periode tagihan/renewal, domain dan biaya disetujui; tidak ada resource dibeli otomatis.
- [ ] PHP web/CLI/extensions dan engine/version DB diperiksa pada akun sebenarnya.
- [ ] Root publik tepat `public/`; config/vendor/storage privat di luar web root.
- [ ] Domain/subdomain dan HTTPS tersedia; perubahan DNS/website existing disetujui.
- [ ] Release ZIP/checksum dan vendor no-dev sesuai source; tidak mengunggah qa-artifacts/workspace lengkap.
- [ ] Target DB baru yang disetujui, bukan DB existing, identitas koneksi dan backup diverifikasi.
- [ ] APP_KEY deployment baru untuk instalasi baru; development key tidak disalin/diubah.
- [ ] Template production diisi privat, debug false, Groq live/demo OFF, provisioning OFF sampai inisialisasi disetujui.

## Instalasi

- [ ] Jalur Artisan aman melalui SSH/Terminal/cron sementara/provider; tidak ada web installer.
- [ ] Platform requirements lulus; storage/bootstrap cache writable tanpa 0777.
- [ ] Migration status lengkap; tidak ada migrate:fresh/reset pada data existing.
- [ ] Initializer disetujui dan dijalankan sekali pada DB kosong; empat credentials unik tersimpan privat 0600.
- [ ] Provisioning dimatikan setelah berhasil; config/route/view cache dibuat ulang pada target.
- [ ] Semua aset Vite 200 dan memakai HTTPS; tidak ada public/hot.
- [ ] Cron CLI sementara dihapus; tidak ada worker/Redis wajib yang dibeli tanpa kebutuhan.

## Keamanan minimum

- [ ] HTTP dialihkan ke HTTPS; Secure/HttpOnly/SameSite cookie sesuai.
- [ ] Pengujian akses `.env`, `.git`, file backup, log, manifest privat dan QA menghasilkan penolakan tanpa mencetak isi.
- [ ] Error 404/419/403/500 tidak menampilkan stack trace, SQL, key atau credential.
- [ ] Tidak ada debug/QA/admin shell/installer publik.
- [ ] Database user dibatasi pada schema demo; privileged accounts diberikan hanya kepada evaluator yang berhak.
- [ ] Backup privat dan restore QA diuji; APP_KEY tersimpan aman.

## E2E desktop dan mobile dengan data sintetis

- [ ] Empat role login/logout dan redirect sesuai; guest/lintas-role ditolak.
- [ ] Mahasiswa: dashboard, pilihan/pengajuan KRS, riwayat, nilai final, IPS/IPK dan histori analisis.
- [ ] Admin: CRUD master, filter/pencarian, validasi/keputusan KRS, proteksi penghapusan berelasi.
- [ ] Dosen: penugasan kelas/roster, kehadiran sesuai fitur existing, input/finalisasi nilai dan final lock.
- [ ] Pimpinan: monitoring/filter/KRS/nilai/kehadiran/risiko/rekomendasi/laporan; tidak ada aksi tulis terlarang.
- [ ] Validasi consent, data kosong, kuota habis, error sementara, double-submit dan back/pageshow tidak mengunci tombol selamanya.
- [ ] Playwright/DevTools: tidak ada error JS/request aset gagal, overflow, screenshot credential, atau browser request langsung ke Groq.

## Groq live — checkpoint terpisah

- [ ] Outbound HTTPS/TLS dan hard timeout provider diverifikasi tanpa menonaktifkan TLS verification.
- [ ] Key dipasang lewat secret configuration server, tidak melalui chat/source/JS/log.
- [ ] Nama DB, ID user allowlist, mode persis dan budget disepakati; provisioning OFF.
- [ ] Pengguna menyetujui batas konsumsi **satu attempt live** awal dan akun/dataset sintetis.
- [ ] Respons benar-benar dari Groq, parent/child tersimpan, provenance live, histori/usage dan attempt sesuai.
- [ ] Refresh/GET/reuse tidak mengirim provider call baru; error tidak bocorkan secret; jangan retry otomatis.
- [ ] Jika gagal: live OFF kembali, periksa histori/budget, laporkan sebelum meminta attempt tambahan.

## Penerimaan dan handoff

- [ ] URL HTTPS yang benar-benar diuji dicatat, berikut versi runtime dan hasil empat role.
- [ ] Cara mematikan Groq, rotasi password, backup, rollback dan penutupan demo dijelaskan.
- [ ] FINAL GO hanya setelah bukti hosting/E2E/live yang disetujui; selama persiapan lokal status tetap CONDITIONAL GO.
