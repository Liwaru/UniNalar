# Penyelesaian modul Owner / Kaprodi

Status: **PASS untuk implementasi fungsional dan QA otomatis yang dijalankan;
PARTIAL untuk penerimaan akhir karena browser visual QA BLOCKED.** Tidak ada
klaim 100% selesai atau sertifikasi siap produksi. Angka laporan Admin sebelumnya
adalah baseline historis; hasil sesi ini dicatat tersendiri di bawah.

## 1. Workspace dan Git — PASS

Workspace WSL `/home/hendra_huang/projects/UniNalar-main`, branch `main`, HEAD
`02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`, remote
`https://github.com/Liwaru/UniNalar.git`. Perubahan lokal Mahasiswa, Dosen, Admin,
serta dokumentasinya dipertahankan. Tidak ada pull, checkout, reset, commit, push,
deployment, perubahan `.env`/Compose, atau akses database operasional.
Vendor/node_modules dan image Docker lokal digunakan tanpa instalasi baru.
Composer/npm lockfile tidak diubah. Tidak ada model, migration, atau izin tulis
akademik baru bagi Owner.

## 2. Seluruh halaman Owner

Semua URL berikut memakai prefix `/dashboard/owner` dan middleware auth + level 4.

| Halaman | URL relatif | Hasil fungsional |
| --- | --- | --- |
| Dashboard | root | FIXED / PASS |
| Mahasiswa | `/mahasiswa` | PASS |
| Ringkasan mahasiswa | `/mahasiswa/{student}` | PASS |
| Dosen | `/dosen` | PASS |
| Kelas dosen | `/dosen/{lecturer}` | PASS |
| Kelas | `/kelas` | PASS |
| Peserta resmi dan penilaian kelas | `/kelas/{classRoom}` | PASS |
| KRS saat ini | `/krs` | PASS |
| Detail KRS dan versi pengajuan | `/krs/{krs}` | PASS |
| Nilai final | `/nilai` | PASS |
| Kehadiran tersimpan | `/kehadiran` | PASS |
| Riwayat risiko tersimpan | `/analisis-risiko` | PASS |
| Faktor dan sumber analisis | `/analisis-risiko/{analysis}` | PASS |
| Rekomendasi tersimpan | `/rekomendasi` | PASS |
| Tindak lanjut tersimpan | `/tindak-lanjut` | PASS |
| Laporan per program studi | `/laporan` | PASS |

Status visual/responsif seluruh baris: BLOCKED, terpisah dari hasil fungsional.

## 3. Halaman baru — PASS

Lima belas endpoint GET ditambahkan: sepuluh daftar/laporan dan lima detail.
Sidebar Owner kini memuat ringkasan dan seluruh daftar. Detail terhubung dari
daftar, kelas dosen, mahasiswa, dan hasil tersimpan. Tidak ada endpoint mutasi
Owner, ekspor, halaman mockup, atau grafik pengisi ruang.

## 4. Halaman diperbaiki dan audit terarah — FIXED

Audit source awal menemukan dashboard Owner PARTIAL, navigasi kosong, dan halaman
detail/monitoring MISSING. Dashboard dan shell Owner kini memiliki breadcrumb,
filter, feedback validasi, serta komponen tabel/detail yang konsisten. Tidak ada
Policy/Form Request/JS Owner khusus sebelumnya; authorization tetap pada
middleware, validasi pada controller/service. `OwnerAcademicData` menyatukan
scope baca dan pilihan filter. `OwnerMonitoringController` menangani halaman
monitoring, sementara dashboard tetap pada controller existing.

Tidak dilakukan refactor global atau perubahan controller modul lain pada sesi
Owner. `AcademicGradeSummary` menerima parameter semester opsional; pemanggil
lama tetap menggunakan perilaku default yang sama.

## 5. Dashboard — PASS

Angka berasal dari database: mahasiswa, dosen, program, mata kuliah, kelas,
keikutsertaan kelas resmi, KRS per status saat ini, jumlah nilai final, catatan
kehadiran, IPS/IPK valid, risiko terbaru per mahasiswa, rekomendasi, dan tindak
lanjut tersimpan. Jumlah peserta adalah keikutsertaan kelas, bukan orang unik.
Data demo dikecualikan melalui scope bersama.

Total profil/program/mata kuliah mengikuti program, tanpa batas semester. Kelas
mengikuti program mata kuliah; KRS/nilai/kehadiran/hasil tersimpan mengikuti
program mahasiswa. Semantik ini dijelaskan di halaman dan diuji untuk pendaftaran
lintas program. Database kosong menampilkan nol untuk jumlah yang diketahui,
serta keadaan belum tersedia untuk nilai. Schema inti yang belum lengkap dan
tabel opsional yang tidak tersedia dibedakan dari hasil filter kosong.

## 6. Monitoring mahasiswa — PASS

Daftar menampilkan NIM, nama, program, angkatan, status, IPS dan IPK. Semester
membatasi mahasiswa yang memiliki KRS pada semester tersebut. Detail menyediakan
ringkasan akademik, KRS berpaginasi, jumlah nilai final/kehadiran, dan tautan ke
catatan akademik mahasiswa. Email, telepon, password, dan identitas akun internal
tidak ditampilkan. Mahasiswa tanpa nilai tidak diberi IPK nol.

## 7. Monitoring dosen dan kelas — PASS

Program dosen mengikuti profil dosen; semester dan jumlah kelas mengikuti semua
penugasan operasional, termasuk mata kuliah lintas program. Program pada daftar
kelas mengikuti mata kuliah. Peserta kelas selalu seluruh peserta resmi,
termasuk mahasiswa lintas program; pending/rejected/dibatalkan dikecualikan.
Jumlah nilai final ditampilkan terhadap jumlah peserta resmi. Nilai draft tidak
dibuka pada roster Owner. Tidak ada status kelas akademik baru yang diciptakan.

## 8. Monitoring KRS — PASS

Daftar menunjukkan kondisi KRS sekarang, filter status, semester/program,
pencarian NIM/nama, dan jumlah detail. Detail memisahkan mata kuliah saat ini
dari snapshot setiap versi pengajuan. Waktu pengajuan/keputusan WIB, status versi,
dan catatan keputusan tersimpan ditampilkan. Dua paginator menggunakan parameter
berbeda. Owner tidak mendapat tombol atau endpoint persetujuan/penolakan.
Riwayat lama tetap terbaca setelah nama mata kuliah berubah.

## 9. Nilai, IPS, IPK — FIXED / PASS

Daftar dan distribusi nilai hanya memakai nilai final pada KRS disetujui dengan
detail diambil. Agregat distribusi mencakup seluruh hasil filter, bukan halaman
aktif. Nilai parsial/tidak ada tidak diganti nol. Tanpa kolom finalisasi, halaman
menyatakan data belum tersedia dan tidak menebak status nilai legacy.

IPS mengikuti semester pilihan; tanpa pilihan memakai semester terbaru yang
memiliki nilai final valid. IPK tetap seluruh riwayat dan memakai percobaan mata
kuliah yang terakhir difinalisasi, sesuai aturan existing. Bobot SKS dan validasi
huruf/mutu tetap melalui service bersama; proteksi perubahan SKS historis dari
modul Admin tetap berlaku. Dokumentasi `academic-operations.md` diperjelas.

## 10. Kehadiran — PASS untuk monitoring; NEEDS SPECIFICATION untuk input

Daftar menampilkan catatan nyata peserta resmi, pertemuan, tanggal, mata kuliah,
semester, serta status tersimpan. Jumlah per status dihitung atas seluruh hasil
filter. Ketiadaan catatan tidak dianggap alpa. Tidak dibuat persentase agregat
baru, denominator pertemuan yang tidak diketahui, atau workflow pencatatan.

## 11. Risiko — PASS untuk hasil tersimpan

Daftar memuat riwayat, sedangkan dashboard menghitung kategori dari analisis
terbaru tiap mahasiswa dalam filter. Detail memuat skor/kategori tersimpan,
waktu WIB, versi model, sumber bila tersedia, serta faktor dengan nilai, bobot,
kontribusi dan penjelasan tersimpan. Faktor dibatasi oleh analisis yang dibuka.
JSON internal tidak ditampilkan mentah. Provenance kosong tidak dinyatakan
sebagai hasil tervalidasi. Penanda demo/provenance simulasi yang dikenal
dikecualikan. Tidak ada skor, kategori, pipeline AI, atau metode baru diaktifkan.

## 12. Rekomendasi — PASS untuk monitoring

Daftar menampilkan mahasiswa yang benar, semester, judul/deskripsi, prioritas,
status, tenggat, dan tautan ke analisis sumber. Status filter berasal dari nilai
yang benar-benar tersimpan dalam scope, tanpa menciptakan transisi/status baru.
Faktor dari analisis berbeda tidak dimuat lewat relasi rekomendasi. Tidak ada
pembuatan rekomendasi otomatis atau kewenangan mengubah rekomendasi.

## 13. Tindak lanjut — PASS untuk monitoring; NEEDS SPECIFICATION untuk mutasi

Relasi rekomendasi → analisis → mahasiswa menentukan scope. Status, rekomendasi,
waktu bila tercatat, dan analisis sumber ditampilkan dengan pagination/filter.
Catatan internal dan identitas pelaksana tidak dibuka karena belum ada kebijakan
akses detail tersebut. Tidak dibuat riwayat perubahan fiktif atau aksi ubah status.

## 14. Laporan akademik — PASS untuk laporan baca-saja

Laporan per program memuat jumlah mahasiswa, mahasiswa dengan KRS disetujui,
kelas, KRS menunggu/disetujui/ditolak, nilai final, analisis tersimpan, dan tindak
lanjut tersimpan. Mahasiswa dihitung unik; analisis/tindak lanjut dihitung per
catatan termasuk riwayat, bukan analisis terbaru. Filter semester membatasi
mahasiswa yang memiliki KRS pada semester itu. Kelas mengikuti program mata
kuliah; catatan mahasiswa mengikuti program mahasiswa. Korelasi agregat diuji
untuk dua program dan pendaftaran lintas program. Tautan membuka ringkasan
program beserta IPS/IPK. Ekspor tidak ditambahkan tanpa spesifikasi kebutuhan
dan otorisasi data sensitif.

## 15. Filter, search, pagination — PASS

Program dan semester divalidasi sebelum query filter lanjutan. Semua ID harus
berasal dari pilihan operasional; array, ID tidak valid, dan q melebihi 255
karakter ditolak. Pencarian `0` diproses; parameter SQL di-bind. Pilihan kelas
nilai/kehadiran mempertimbangkan mahasiswa lintas program. Label kelas memakai
kode kelas, mata kuliah dan semester, bukan ID teknis.

Daftar memakai 20 baris; KRS detail mahasiswa 15; versi pengajuan 10 dengan
`versions_page`. Query string dipertahankan. Filter status tersedia pada KRS,
rekomendasi, dan tindak lanjut. Detail tetap menghormati program/semester yang
dipilih dan mengembalikan 404 bila record berada di luar filter tersebut.

## 16. Integrasi empat role — PASS

Skenario otomatis menguji Admin memperbarui kelas → Mahasiswa mengajukan KRS →
Owner membaca pending → Admin menolak → Mahasiswa mengajukan versi baru → Admin
menyetujui → Dosen membaca peserta resmi/mengisi dan memfinalisasi nilai →
Mahasiswa dan Owner membaca nilai/IPK yang sama. HTTP juga menguji CRUD enam
master dan persistensi akhirnya. Hasil risiko/rekomendasi pada QA adalah fixture
sintetis yang ditandai, bukan hasil engine atau bukti validitas metode akademik.
Tidak ada data pribadi nyata digunakan atau hasil analisis produksi dibuat.

## 17. Security dan authorization — PASS untuk regresi yang diuji

Guest diarahkan ke login; Mahasiswa/Dosen/Admin ditolak pada seluruh endpoint
Owner. Owner ditolak pada mutasi Admin/Dosen. POST pada halaman monitoring
menghasilkan 405. Route parameter detail numerik dan pengambilan record memakai
scope operasional. CSRF aktif pada HTTP QA; login/logout/session diuji lintas
role. Blade meng-escape payload metadata/provenance, pencarian SQL-like tidak
membuka seluruh data, demo dikecualikan, dan data pribadi/catatan internal tidak
terekspos pada halaman monitoring. Tidak ada mass-assignment/write Owner.

Tidak ada pemetaan akun Owner ke program studi pada schema/source. Hak baca
level 4 tetap institusi-wide sesuai dokumentasi existing; filter bukan pembatas
otorisasi per akun. Pembatasan Kaprodi ke program tertentu membutuhkan spesifikasi
dan relasi otorisasi resmi. Hasil ini bukan audit penetrasi menyeluruh.

## 18. Natural UI/UX — PASS pada implementasi; visual NOT VERIFIED

Identitas, shell, tipografi dan palet UniNalar dipertahankan. CSS akademik existing
digunakan ulang oleh Owner, dengan partial filter, tabel kelas, empty state,
pagination dan layout. Dashboard memakai kelompok informasi yang jelas dan
tabel ringkas. Tidak ada gradient/glow/glassmorphism/grafik/animasi baru. Warna
status disertai teks. Form filter punya label, state, reset dan feedback. Sidebar
Owner dapat digulir agar navigasi panjang tidak menutupi logout.

## 19. Responsive readiness — PARTIAL

Pemeriksaan source memastikan grid responsif, tabel di scroll region yang dapat
difokuskan, kontrol filter membungkus pada layar sempit, dan focus outline
tersedia. Desktop/mobile memakai shell bersama. Viewport 375×812, 390×844,
768×1024, 1366×768, dan 1920×1080 seluruhnya **NOT VERIFIED secara visual**.
Kontras, urutan fokus, overflow aktual dan kepadatan layar menunggu browser QA.

## 20. SQLite — PASS

Hasil final: **119 tes, 1.905 assertions PASS**, termasuk regresi seluruh role.
Suite memverifikasi
`APP_ENV=testing` dan SQLite `:memory:` sebelum RefreshDatabase melakukan write.
Dua belas tes Owner baru meliputi seluruh halaman/role, parameter manipulasi,
semantik IPS/IPK, workflow empat role, roster resmi, hasil tersimpan, pagination,
XSS/privacy, skala query, lintas program, schema kosong dan legacy finalisasi.

## 21. MySQL — PASS

Hasil final: **57 tes, 989 assertions PASS**, termasuk sembilan skenario
concurrency. Server MySQL 8.4 QA baru
menggunakan tmpfs `/var/lib/mysql`, network internal, tanpa port publik atau
volume data persisten. Manifest diverifikasi terhadap server UUID aktual,
hostname, database `qa_` unik dan marker server sebelum migration/fixture write.
Schema operasional `uninalar` dipastikan tidak ada pada server baru itu; server
operasional tidak dihubungi. Sepuluh skenario Owner yang sama dijalankan bersama
regresi MySQL existing. MySQL harness membersihkan resource dan file kredensial.

## 22. Concurrency — PASS

Sembilan skenario existing dijalankan kembali: semester aktif bersaing, kapasitas
melawan submit, submit ganda, kursi terakhir, keputusan KRS bersaing, pembuatan
nilai ganda, finalisasi melawan edit, revisi kedaluwarsa, dan hapus kelas berelasi.
Proses/koneksi terpisah dan penguncian diperiksa. Owner read-only sehingga tidak
ditambahkan race mutasi Owner. Nilai deadlock status yang tidak tersedia bukan
bukti nol deadlock atau jaminan semua interleaving produksi aman.

## 23. HTTP end-to-end — PASS

Run HTTP terbaru mencatat 308 pemeriksaan. Proses fixture memverifikasi file
SQLite baru di `/tmp` dan identitas `PRAGMA database_list` sebelum migration.
Server localhost berada dalam container QA tanpa published port; APP_ENV local
hanya pada proses HTTP agar CSRF benar-benar aktif. Semua halaman Owner,
detail/filter/empty state, versi KRS, draft vs final, sumber faktor, pagination
hasil tersimpan, privacy, cross-role denial, serta regresi Admin/Mahasiswa/Dosen
diuji. Persistensi akhir database QA diperiksa. Ini bukan browser rendering.

## 24. PHP, Pint, Vite — PASS

PHP 8.2.34 dari image lokal dipakai karena PHP host tidak tersedia. Syntax PASS
116 file PHP; Pint test mode PASS 117 file source. Vite PASS 127 transformed
modules. Tidak ada dependency upgrade, install global, atau perubahan lockfile.
Asset dashboard CSS 281,15 kB (gzip 42,11 kB), CSS akademik 5,87 kB (gzip 1,67 kB).
Semua asset pada manifest diperiksa HTTP 200 oleh harness.

## 25. Browser QA — BLOCKED

Playwright MCP dan Chrome DevTools MCP tidak tersedia di katalog tool sesi ini.
Tidak ada dependency browser diinstal. Screenshot, console/network browser,
rendering aktual, serta lima viewport tidak diklaim telah diuji. HTTP200, build,
dan review CSS tidak menggantikan QA visual.

## 26. Warning CSS — FIXED

Sumber warning adalah `@charset "UTF-8"` pada dependency
`node_modules/bootstrap/dist/css/bootstrap.min.css`, yang diimpor ke dalam
`@layer` pada CSS dashboard dan landing. At-rule charset yang tersarang tidak
valid; warning terjadi saat optimizer Tailwind membaca hasil import.

Perbaikan terbatas pada Vite: optimasi prematur Tailwind dimatikan; PostCSS
membuang deklarasi charset import; minifikasi tetap dilakukan Vite sesudahnya.
Tidak mengedit vendor atau menaikkan dependency. Build final tidak lagi mencatat
warning charset. Ukuran gzip dashboard naik sekitar 0,4 kB dibanding run awal
sesi ini karena perbedaan optimizer; tidak diklaim ada peningkatan ukuran/render
atau kesetaraan visual yang belum diverifikasi browser.

## 27. Bugs fixed dan performa

FIXED: navigasi/monitoring Owner belum tersedia; semester tidak memengaruhi IPS
dashboard; tidak ada pembacaan final-grade/roster/riwayat terperinci; filter dosen
semester sempat mengecualikan pengampu mata kuliah lintas program; dan warning
charset. Empty/schema state dan pilihan kelas lintas program juga diperbaiki.

Tes skala query membandingkan satu dan 20 mahasiswa/roster; query tidak bertambah
satu kali per baris. Relasi tabel memakai eager loading/count dan laporan memakai
subquery agregat. Tidak ada cache statistik akademik baru. Dashboard GPA masih
memproses seluruh nilai final valid mahasiswa dalam program yang dipilih secara
batch; peak memory dan waktu pada data produksi besar **NOT VERIFIED**. Tidak ada
klaim benchmark/load-test produksi atau optimasi spekulatif.

Kegagalan tes awal ditangani: section Blade pada schema kosong memerlukan iterable
aman; nama route tes nilai diperbaiki; kategori tersimpan dipertahankan agar
regresi existing tetap berlaku; assertion MySQL baru diberi scope program
fixture karena skenario race menyimpan fixture committed. Tidak ada tes existing
dihapus atau assertion integritas dilemahkan.

## 28. Remaining blockers

BLOCKED: browser/responsive visual acceptance. NOT VERIFIED: hosting produksi,
TLS/proxy/session deployment, audit keamanan menyeluruh, load/peak memory produksi,
backup/restore data nyata, dan rollout migration operasional. Container operasional
tidak dihentikan atau dibuat ulang untuk QA ini.

## 29. Needs specification

Pemetaan Kaprodi ke program tertentu; kebijakan publikasi catatan internal dan
identitas pelaksana; format/otorisasi ekspor; input/koreksi kehadiran; transisi
rekomendasi/tindak lanjut; pipeline AI, provenance resmi, formula/bobot/threshold
risiko; pembukaan nilai final dan koreksi SKS historis. Tidak ada kebijakan baru
yang diasumsikan untuk melengkapi ruang lingkup tersebut.

## 30. Final verdict

**PASS untuk fitur Owner baca-saja yang terdefinisi dan regresi otomatis;
PARTIAL untuk penerimaan keseluruhan karena browser QA BLOCKED.** Implementasi
berupa source frontend/backend, integrasi query/navigasi dan tes, bukan hanya
dokumentasi. Bukti lokal berada pada `qa-artifacts/owner/` (ignored): logs SQLite,
MySQL/races/manifest, HTTP checks, lint, Pint, dan build. Laporan modul sebelumnya
dipertahankan sebagai snapshot historis. Container PHP/HTTP QA sudah dihapus;
network/container MySQL QA dibersihkan harness. Pemeriksaan read-only memastikan
keempat container operasional tetap berjalan. `git diff --check` PASS; asset
manifest tersedia dan CSS hasil build tidak mengandung charset import.
Tidak dilakukan commit/push/deployment.
