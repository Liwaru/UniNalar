# Matriks halaman UI/UX UniNalar

Inventaris 8 November 2026 dari route Laravel aktual, dicocokkan ke view sumber
serta 60 URL fixture browser. Parameter `{...}` memakai ID sintetis yang valid.
Semua baris diperiksa pada lima viewport dan diambil screenshot; tinjauan visual
keseluruhan menggunakan contact sheet desktop serta contoh mobile terpilih.

**Desain D** = DOM/struktur + screenshot, bukan sertifikasi aksesibilitas.
**Responsif R5** = lima viewport tanpa overflow dokumen; tabel dapat scroll lokal.
**Interaksi G** = render GET dan pemeriksaan kontrol; filter form GET utama pada
halaman yang menyediakannya diuji terpisah dalam browser. **M** = kontrak mutatif
memakai regresi SQLite terisolasi, bukan klaim klik seluruh aksi browser.
Rincian interaksi khusus dan batas ada pada [laporan final](ui-ux-final-refinement.md).

| Route | Role | View (`resources/views`, `.blade.php`) | Fungsi/nama route | Desain | Interaksi | Responsif | Temuan |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `/` | Publik | `landing/index` | `/` | D | G | R5 | Metrik/ilustrasi rekaan diganti layanan aktual |
| `/dashboard/admin-akademik` | admin-akademik | `dashboard/admin-akademik/index` | `dashboard.admin-akademik` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/dosen` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.dosen.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/dosen/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.dosen.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/dosen/{record}` | admin-akademik | `dashboard/admin-akademik/resource-detail` | `dashboard.admin-akademik.dosen.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/dosen/{record}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.dosen.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/kelas-kuliah` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.kelas-kuliah.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/kelas-kuliah/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.kelas-kuliah.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah}` | admin-akademik | `dashboard/admin-akademik/kelas-detail` | `dashboard.admin-akademik.kelas-kuliah.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.kelas-kuliah.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/krs-pengajuan` | admin-akademik | `dashboard/admin-akademik/krs-review` | `dashboard.admin-akademik.krs-pengajuan.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/krs-pengajuan/{history}` | admin-akademik | `dashboard/admin-akademik/krs-detail` | `dashboard.admin-akademik.krs-pengajuan.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mahasiswa` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.mahasiswa.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mahasiswa/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.mahasiswa.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mahasiswa/{record}` | admin-akademik | `dashboard/admin-akademik/resource-detail` | `dashboard.admin-akademik.mahasiswa.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mahasiswa/{record}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.mahasiswa.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mata-kuliah` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.mata-kuliah.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mata-kuliah/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.mata-kuliah.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mata-kuliah/{record}` | admin-akademik | `dashboard/admin-akademik/resource-detail` | `dashboard.admin-akademik.mata-kuliah.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/mata-kuliah/{record}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.mata-kuliah.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/program-studi` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.program-studi.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/program-studi/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.program-studi.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/program-studi/{record}` | admin-akademik | `dashboard/admin-akademik/resource-detail` | `dashboard.admin-akademik.program-studi.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/program-studi/{record}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.program-studi.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/semester` | admin-akademik | `dashboard/admin-akademik/resource-index` | `dashboard.admin-akademik.semester.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/semester/create` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.semester.create` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/semester/{record}` | admin-akademik | `dashboard/admin-akademik/resource-detail` | `dashboard.admin-akademik.semester.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/admin-akademik/semester/{record}/edit` | admin-akademik | `dashboard/admin-akademik/resource-form` | `dashboard.admin-akademik.semester.edit` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen` | dosen | `dashboard/dosen/index` | `dashboard.dosen` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/kelas` | dosen | `dashboard/dosen/kelas` | `dashboard.dosen.kelas.index` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/kelas/{kelas}` | dosen | `dashboard/dosen/kelas-detail` | `dashboard.dosen.kelas.show` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/kelas/{kelas}/kehadiran` | dosen | `dashboard/dosen/kehadiran` | `dashboard.dosen.kelas.kehadiran` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/kelas/{kelas}/peserta` | dosen | `dashboard/dosen/peserta` | `dashboard.dosen.kelas.peserta` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/nilai` | dosen | `dashboard/dosen/nilai` | `dashboard.dosen.nilai` | D | G + M | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/dosen/profil` | dosen | `dashboard/dosen/profil` | `dashboard.dosen.profil` | D | G + M | R5 | Error terhubung ke field dan summary |
| `/dashboard/mahasiswa` | mahasiswa | `dashboard/mahasiswa/index` | `dashboard.mahasiswa` | D | G + M | R5 | Grafik: tabel alternatif, label, reduced motion |
| `/dashboard/mahasiswa/analisis-risiko` | mahasiswa | `dashboard/mahasiswa/analisis-risiko` | `dashboard.mahasiswa.analisis-risiko` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/kehadiran` | mahasiswa | `dashboard/mahasiswa/kehadiran` | `dashboard.mahasiswa.kehadiran` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/krs` | mahasiswa | `dashboard/mahasiswa/krs` | `dashboard.mahasiswa.krs` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/nilai` | mahasiswa | `dashboard/mahasiswa/nilai` | `dashboard.mahasiswa.nilai` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/profil` | mahasiswa | `dashboard/mahasiswa/profil` | `dashboard.mahasiswa.profil` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/rekomendasi` | mahasiswa | `dashboard/mahasiswa/rekomendasi` | `dashboard.mahasiswa.rekomendasi` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/mahasiswa/tindak-lanjut` | mahasiswa | `dashboard/mahasiswa/tindak-lanjut` | `dashboard.mahasiswa.tindak-lanjut` | D | G + M | R5 | Tidak ada blocker pada pemeriksaan ini |
| `/dashboard/owner` | owner | `dashboard/owner/index` | `dashboard.owner` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/analisis-risiko` | owner | `dashboard/owner/risks` | `dashboard.owner.analisis-risiko.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/analisis-risiko/{analysis}` | owner | `dashboard/owner/risk` | `dashboard.owner.analisis-risiko.show` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/dosen` | owner | `dashboard/owner/lecturers` | `dashboard.owner.dosen.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/dosen/{lecturer}` | owner | `dashboard/owner/lecturer` | `dashboard.owner.dosen.show` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/kehadiran` | owner | `dashboard/owner/attendance` | `dashboard.owner.kehadiran.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/kelas` | owner | `dashboard/owner/classes` | `dashboard.owner.kelas.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/kelas/{classRoom}` | owner | `dashboard/owner/class` | `dashboard.owner.kelas.show` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/krs` | owner | `dashboard/owner/krs` | `dashboard.owner.krs.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/krs/{krs}` | owner | `dashboard/owner/krs-detail` | `dashboard.owner.krs.show` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/laporan` | owner | `dashboard/owner/reports` | `dashboard.owner.laporan.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/mahasiswa` | owner | `dashboard/owner/students` | `dashboard.owner.mahasiswa.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/mahasiswa/{student}` | owner | `dashboard/owner/student` | `dashboard.owner.mahasiswa.show` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/nilai` | owner | `dashboard/owner/grades` | `dashboard.owner.nilai.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/rekomendasi` | owner | `dashboard/owner/recommendations` | `dashboard.owner.rekomendasi.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/dashboard/owner/tindak-lanjut` | owner | `dashboard/owner/follow-ups` | `dashboard.owner.tindak-lanjut.index` | D | G | R5 | Feedback/validasi shell bersama; workflow dipertahankan |
| `/login` | Publik | `auth/login` | `login` | D | G | R5 | Loading, fokus error, target password |
