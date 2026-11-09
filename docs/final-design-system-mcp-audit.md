# Audit akhir konsistensi desain, CSS dan browser UniNalar

> Path `/path/to/UniNalar-main`, `~`, `<windows-user-home>` dan `<runtime-tag>`
> adalah placeholder publik pengganti identitas mesin pada bukti lokal. Ganti sesuai
> workspace/runtime development yang sudah diverifikasi sebelum memakai contoh perintah.

> Laporan historis sebelum MCP callable. Bukti dual-MCP aktual terbaru berada di
> [full-system QA](final-full-system-dual-mcp-qa.md).

8 November 2026 · source WSL · `http://localhost:8140` · Chrome 156.0.8078.81.

**PARTIAL secara keseluruhan:** implementasi dan QA alternatif **PASS**, tetapi
Playwright MCP dan Chrome DevTools MCP masing-masing **BLOCKED** pada sesi ini.
Tidak ada klaim MCP PASS. Pengujian di bawah dijalankan ulang pada pekerjaan ini;
HTTP 200/satu request/526 token Groq adalah hasil **historis**, bukan request baru.

Workspace tetap branch `main`, baseline HEAD `3ff941b`; perubahan lokal sebelumnya
dipertahankan. Container development `uninalar-wsl-dev-<runtime-tag>` memakai bind
source `/path/to/UniNalar-main`, server PHP WSL-router, port loopback
8140 dan SQLite bertag yang lolos `WslDevelopmentIdentity::verify()`. MySQL operasional,
source Windows lama, volume, `.env`, APP_KEY dan konfigurasi production tidak diubah.
Tidak ada instalasi, staging, commit, push atau deployment.

Pedoman utama: [UI/UX UniNalar](ui-ux-guidelines.md). Skill lokal
[UI/UX Pro Max](../.agents/skills/ui-ux-pro-max/SKILL.md) dipakai sebagai referensi
semantic tokens, konsistensi kontrol dan kontras, tanpa mengganti palet/stack UniNalar.

## 1. Ketersediaan Playwright MCP — BLOCKED

Pencarian metadata `ALL_TOOLS` berdasarkan nama/deskripsi `playwright|devtools`
tidak menemukan tool Playwright MCP yang dapat dipanggil pada sesi Codex ini.
Ini bukan pernyataan bahwa paket tidak pernah terpasang pada komputer pengguna.
Tidak mengubah konfigurasi MCP/global atau memasang software.

## 2. Ketersediaan Chrome DevTools MCP — BLOCKED

Tidak ada tool Chrome DevTools MCP yang diekspos pada sesi ini. Pemeriksaan terpisah
dicatat di `qa-artifacts/design-system/mcp-availability.json`. Penggunaan CDP melalui
Playwright tidak mengubah status ini menjadi PASS.

## 3. Bukti penggunaan kedua MCP — BLOCKED; alternatif PASS

Tidak ada pemanggilan MCP browser yang bisa dibuktikan. Alternatif yang benar-benar
digunakan: Playwright cache existing, Chrome Windows existing dari WSL, dan CDP
`Network`, `Runtime`, `Performance`, `DOM`, `CSS`. Tidak ada dependency baru.
Bukti lokal di `qa-artifacts/design-system/` diabaikan Git; laporan dan matriks
halaman merupakan dokumentasi source. Jangan memasukkan sesi browser/fixture SQLite
ke Git. Kredensial fixture diberikan melalui stdin dan tidak dicetak.

## 4. Inventaris halaman — PASS

`routes.json`: 97 route aktual, 85 endpoint akademik; 60 halaman HTML:
2 publik, 8 Mahasiswa, 7 Dosen, 27 Admin Akademik, 16 Owner. Inventaris dicocokkan
kembali terhadap route dan keberadaan view, termasuk list/detail/create/edit.
Alias, redirect, API JSON dan endpoint mutatif bukan halaman visual tambahan.

[Matriks 60 halaman](design-system-page-matrix.md) mencatat route, role, view, CSS,
komponen, warna, font, button, form, table, layout, responsivitas dan interaksi.
`page-inventory.json` menyimpan padanan mesin. KRS/riwayat dan IPS/IPK sebagian berada
dalam halaman terkait, bukan route tambahan yang dibuat demi memenuhi daftar.
Error, empty, validation dan loading diverifikasi sebagai state.

## 5. Sumber CSS yang diaudit — FIXED

| File | Efek perubahan | Bukti hasil |
| --- | --- | --- |
| `resources/css/design-tokens.css` (baru) | Satu sumber warna, font, ukuran, spacing, radius; alias body Bootstrap | Primary dan body identik pada 300 kasus computed style |
| `resources/css/student-dashboard.css` | Kontrak kontrol semua role, tabel, heading, focus, sidebar scroll dan token | Matriks 300, assertions interaksi, CDP lintas role |
| `resources/css/admin-academic.css` | Hapus override font/kontrol yang sudah diatur base; pertahankan layout master/monitoring | 135 kasus Admin + 80 Owner; CRUD, validasi, pagination |
| `resources/css/login.css` | Token, kontrol 44px/radius 8px, target dialog/toggle, ruang teks password, shadow lebih ringan; hapus `.field-error` yang tidak direferensikan | Fokus/toggle/dialog, matriks login dan retest final publik |
| `resources/css/landing.css` | Token, pulihkan base/skip link; navbar memakai aturan sendiri; konsolidasi duplikat | Landing 5 viewport, menu keyboard, skip focus, retest final |
| `resources/css/app.css` | Alias font Tailwind ke font UniNalar dan impor token | Build Vite PASS; entry welcome legacy tidak diroute ke halaman aktif |

Bootstrap tetap digunakan melalui import existing; Tailwind/Vite tetap pipeline
existing, tanpa framework baru. `layouts/dashboard` memuat base satu kali, lalu
CSS Admin untuk Admin/Owner; CSS Admin tidak mengimpor ulang token. Public/login
memakai entry masing-masing. Variabel Bootstrap body kini merujuk token bersama,
menghilangkan benturan system-ui versus Jakarta.

Audit PostCSS source sebelum/sesudah: literal hex berulang **337 → 102** (di luar
file token), duplikat selector dalam konteks media yang sama **2 → 0**, `!important`
**8 → 4**. Empat sisanya hanya reduced-motion universal yang disengaja. Selector
berulang pada media berbeda bukan otomatis duplikat. Media condition yang sama
dikonsolidasi dengan urutan cascade dipertahankan; diuji di lima viewport.

Tidak ada inline `<style>`/`style=` pada view halaman aktif yang dipindai. Template
`welcome.blade.php` legacy berisi fallback inline tetapi tidak dipakai route aktual;
tidak dihapus. JS navbar hanya mengubah state/class Bootstrap; login/form-feedback
mengelola fokus/loading; Chart.js membaca token dan font computed. Tidak ada warna
runtime acak per role. CSS kondisional/responsif dan vendor tidak dipurge berdasarkan
coverage satu halaman. Byte source, media query, warna dan duplikat ada di
`before-css.json`/`after-css.json`.

## 6. Design tokens — FIXED

Sumber: `resources/css/design-tokens.css`. Primary `#2563eb`, hover `#1d4ed8`,
secondary `#334155`, background `#f8fafc`, surface putih, text `#14213d`, muted
`#526178`, border `#cbd5e1`, divider `#e7edf3`, success `#087a65`, warning `#906113`,
danger `#b42318`, focus `#2563eb`, masing-masing status dengan background lembut.
Font Jakarta; body 14px, small 13px, caption 12px, judul akademik 26px, line-height
1,6, radius kontrol 8px/panel 12px, kontrol 44px dan spacing 4/8/12/16/20/24/32px.
Token dipakai langsung oleh komponen; variasi dekoratif identitas tidak dipaksa
menjadi satu warna. Tabel/form panjang mempertahankan layout sesuai fungsi.

## 7. Konsistensi warna — PASS

Computed primary `#2563eb` pada **300/300** kasus, body `rgb(20, 33, 61)` lintas
role. Landing putih; auth/dashboard `#f8fafc`, panel putih. Ini perbedaan konteks
yang disengaja. Badge low/medium/high memakai success/warning/danger; analisis
belum diklasifikasikan tetap netral dan berlabel, bukan peringatan risiko resmi.
Status draft/final tetap tekstual. Grafik membaca primary/muted/divider dari token.

Kontras pasangan token dihitung lokal, bukan perkiraan screenshot: teks/putih
15,97:1; muted/putih 6,29:1; putih/primary 5,17:1; success 4,80:1; warning 4,93:1;
danger 5,93:1; teks disabled 5,10:1; unclassified 5,74:1. Rincian `contrast.json`.
Ini pemeriksaan pasangan utama, bukan sertifikasi semua piksel/semua state WCAG.

## 8. Konsistensi tipografi — FIXED

Reproduksi awal: body Mahasiswa/Dosen memakai font system-ui Bootstrap, Admin/Owner
memakai Jakarta. Setelah alias dan penghapusan override, **300/300** body memakai
`"Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif`, 14px/22,4px.
CDP actual platform font pada h1 sampel lima kelompok publik/role menunjukkan
Plus Jakarta Sans custom font; sampel publik CDP adalah login. Landing diverifikasi
melalui computed styles dan screenshot. Font eksternal memiliki fallback lokal.
Judul, subjudul 18px, subbagian 16px, tabel 13px dan caption 12px punya hierarki;
hero/branding mempertahankan ukuran editorial existing. Bukan semua teks dibuat 14px.

## 9. Button — FIXED

Primary akademik bersama: minimum 44px, radius 8px, 13px/600, padding 8×16px;
secondary putih/border, danger merah, hover lebih gelap, focus 3px dan disabled
netral. Tombol finalisasi Dosen dan tambah/ajukan/hapus KRS memakai kelas kontrak
bersama; action/endpoint tidak berubah. Hapus KRS memakai varian danger.
Loading tetap memakai `aria-busy` dan disabled dari form-feedback existing.
CTA hero 52px dan pagination ringkas 40px adalah varian fungsional, bukan ukuran
primer yang berbeda antar-role. Tidak ada klaim semua tautan inline bertarget 44px.

## 10. Form — FIXED

Input/select/textarea akademik: border/radius/padding/tinggi dasar sama, teks 14px
desktop dan 16px mobile; label, help, required dan validasi existing dipertahankan.
Readonly/disabled netral, invalid memakai border danger, fokus terlihat lintas role.
Password toggle tidak menutupi teks; login input dan tombol 44px, modal help memakai
target yang dapat dioperasikan. Validasi Dosen mempertahankan input dan fokus error;
Admin validasi required, pencarian kosong dan CRUD record sementara PASS.
Tidak mengubah aturan backend atau menurunkan assertions tes.

## 11. Table — FIXED

Header 12px/700 dengan latar netral, isi 13px, padding 10×12px, divider dan angka
tabular bersama. Scroll region lokal existing tetap dapat diakses keyboard;
tabel panjang tidak memperlebar dokumen. Tabel alternatif grafik dua kolom tetap
muat di 375/390/768/1366/1920. Nilai, KRS, master dan monitoring memakai pola sama;
kolom identitas/action tidak dihilangkan demi screenshot. Pagination Admin/Owner,
empty search dan status draft/final terverifikasi dalam interaksi.

## 12. Sidebar/navbar/breadcrumb — FIXED

Sidebar menggunakan komponen existing semua role, Jakarta, active blue dan target
44px; overflow-y desktop memungkinkan menu panjang dijangkau. Grid mobile tetap
mengikuti pola existing. Satu breadcrumb bersama kini berada **sebelum h1** untuk
keempat role, bukan Mahasiswa di atas judul dan lainnya di bawah. Sidebar tetap
memiliki satu `aria-current="page"`; tidak menggandakan penanda aktif global.
Navbar publik tetap Bootstrap collapse, dengan CSS UniNalar untuk alignment.

## 13. Landing dan login — FIXED

Landing skip link sebelumnya terlihat tanpa fokus karena base CSS hilang pada
pembersihan lama; sekarang tersembunyi sampai keyboard focus. Navigasi responsive,
CTA, logo dan konten layanan existing dipertahankan. Login memakai keluarga warna
sama, username/password empat role, dialog bantuan, focus dan loading yang berfungsi.
Shadow dialog diringankan; tidak menambah gradient/glow/card dekoratif.
Retest **10 render publik** setelah cleanup CSS terakhir PASS dengan 0 event error.

## 14. Mahasiswa — PASS

8 halaman × 5 viewport = **40** kasus. Dashboard/chart alternatif, profil, KRS,
nilai/IPS/IPK, kehadiran, analisis, rekomendasi dan tindak lanjut diperiksa. Tombol
KRS berbagi kontrak baru. Lima filter utama dan login/logout/authorization diuji.
Analisis Hendrik dan tiga rekomendasi live tersimpan terlihat; simulasi lama tidak
diberi label live. KRS mutatif dijaga suite SQLite, bukan diubah pada fixture final.

## 15. Dosen — PASS

7 halaman × 5 = **35** kasus: dashboard, kelas/detail/peserta, kehadiran, profil,
pengelolaan nilai. Filter, tautan detail, error focus/association/input retained dan
login/logout PASS. Input dan finalisasi memakai kontrol bersama; kontrak draft,
revisi/finalisasi dan authorization lulus regresi SQLite. Tidak menyimpan perubahan
nilai final existing hanya untuk menguji warna tombol.

## 16. Admin Akademik — PASS

27 halaman × 5 = **135** kasus, termasuk enam master dengan create/edit/detail,
dashboard dan review/detail riwayat KRS. Tabel dan form panjang memakai base baru.
Pagination, search/empty, validation, cancel-delete, loading dan delapan filter
utama PASS. Browser membuat/mengedit/menghapus **hanya program studi QA sendiri**;
double-submit menghasilkan satu POST. Tidak menghapus record existing.

## 17. Owner/Kaprodi — PASS

16 halaman × 5 = **80** kasus: monitoring/dashboard/list/detail/laporan. Enam belas
filter GET utama, pagination kehadiran, search kosong/reset dan read-only PASS.
Detail analisis ID 5 dan rekomendasi tersimpan cocok dengan Mahasiswa. Tidak
menambahkan kontrol mutatif; permintaan Admin dari role lain tetap ditolak.

## 18. Accessibility — PASS pada pemeriksaan yang dijalankan; PARTIAL menyeluruh

300 kasus: tidak ada input terlihat tanpa label, duplicate ID, broken aria reference
atau gambar gagal. Keyboard skip link, password toggle, containment dialog/Escape/
return-focus, nav mobile dan fokus validasi diuji. Focus ring solid 3px dan target
toggle 44px dikonfirmasi computed; muted/body/status token memenuhi pasangan
kontras di bagian 7. Reduced-motion dihormati termasuk Chart.js. Semantik heading,
label form dan alternatif tabel grafik dipertahankan. Reflow pada viewport setara
200% diuji; **bukan** pengujian native browser zoom seluruh halaman.
Belum diuji dengan screen reader fisik, seluruh kombinasi hover/status, atau audit
WCAG menyeluruh. Jangan menyimpulkan sertifikasi aksesibilitas.

## 19. Mobile/tablet/desktop — PASS pada matriks yang diuji

375×812, 390×844, 768×1024, 1366×768 dan 1920×1080: **300/300 render HTTP 200**,
tanpa overflow dokumen, label/ARIA rusak, duplicate ID, secret-format marker,
gambar rusak atau CSS/JS gagal yang teramati. Semua halaman diambil screenshot;
tinjauan visual mencakup lima contact sheet seluruh 60 halaman desktop dan contoh
full-page mobile/login/form panjang/nilai serta detail Owner. Tidak mengklaim
setiap piksel 300 screenshot diperiksa manual.

Sampel CLS maksimum **0,01678** dalam browser lokal reduced-motion, setelah font
ready dan jeda 80ms; ini diagnostik lokal, bukan metrik pengguna lapangan.
Pembersihan final dua `!important` navbar dan satu selector login mati diikuti
build + 10 render publik baru. Matriks 300 sebelumnya tetap relevan untuk bagian
akademik yang tidak berubah oleh cleanup tersebut.

## 20. Bug yang ditemukan

| Reproduksi | Akar masalah | Dampak |
| --- | --- | --- |
| Bandingkan computed body Mahasiswa/Dosen dengan Admin/Owner | Alias Bootstrap dan override role berbeda | Font terlihat seperti modul berbeda |
| Bandingkan tombol form/filter/KRS/finalisasi | Deklarasi common tersebar pada role/form | Tinggi sekitar 39–44px dan radius berbeda |
| Buka landing tanpa menekan Tab | Base `.landing-page`/skip-link hilang pada cleanup terdahulu | Skip link muncul sebagai konten biasa |
| Bandingkan posisi breadcrumb sebelum/sesudah judul empat role | Empat blok layout terpisah | Urutan navigasi tidak konsisten |
| Audit source selector/context | `.footer-bottom` dan `.password-toggle` berulang; override navbar redundant | Cascade sulit ditelusuri |
| Periksa login/mobile dan menu desktop panjang | Kontrol/help target dan overflow sidebar tidak diseragamkan | Target/interaksi tidak konsisten |

## 21. Bug yang diperbaiki — FIXED

Font: token + alias Bootstrap, bukti computed dan CDP bagian 8.
Kontrol: kontrak CSS bersama + kelas action pada Blade, bukti bagian 9–11 dan
interaksi. Skip link: pulihkan base dari layout yang benar-benar dirender,
bukti keyboard/final-public. Breadcrumb: satu blok role map, bukti urutan DOM
seluruh matriks dan tes sidebar aktif tetap lulus. Cascade: konsolidasi selector
persis sama/media yang sama, hapus hanya dead `.field-error` setelah pencarian
referensi; 0 duplicate context pada enam file. Navbar: hapus empat `!important`
redundant; empat reduced-motion tetap dipertahankan. Sidebar overflow dan target
login: computed dan screenshot terpilih. Tidak melakukan purge CSS/vendor massal.

Percobaan build di container sempat gagal karena npm tidak tersedia di image PHP;
build final memakai npm/Node WSL yang sudah tersedia dan PASS. Probe publik sempat
timeout karena selector QA salah (`.public-skip-link`); diperbaiki ke kelas aktual
`.skip-link`, retest 10/10 PASS. Keduanya masalah harness/tooling, tidak disembunyikan
sebagai hasil tes produk yang lulus.

## 22. Playwright MCP results — BLOCKED; Playwright alternatif PASS

Bukti baru:

- Baseline sebelum perubahan: 120 render (60 × dua viewport), `before-audit.json`.
- Sesudah: **300 render/screenshot**, `after-audit.json`, 0 events error/warning.
- `browser-summary.json`: **192 assertions PASS**, 0 issues: 127 pemeriksaan
  interaksi/fungsi, 60 referensi favicon dan 5 ukuran tabel alternatif grafik.
  Angka 192 bukan 192 workflow berbeda.
- Public final setelah cleanup: **10 render PASS**, `public-final.json`.
- Groq saved-result terpisah: **45 render + 98 assertions + 7 HTTP PASS**,
  `groq-browser-summary.json` (tidak menimpa hasil 192 assertions).

Tidak menjumlahkannya sebagai jumlah halaman unik. Browser mutasi dibatasi record
QA miliknya sendiri; pengujian KRS/finalisasi lengkap berada di regresi SQLite.

## 23. Chrome DevTools MCP results — BLOCKED; CDP alternatif PASS

CDP benar-benar dipanggil melalui Playwright `newCDPSession`: `DOM.getDocument`,
`DOM.querySelector`, `CSS.getComputedStyleForNode`, `CSS.getMatchedStylesForNode`,
`CSS.getPlatformFontsForNode`, `Performance.getMetrics`, Network dan Runtime.
`after-audit.json/checks[].cdpCss` menyimpan computed properties, matched selector
dan actual font lima kelompok; `consistency.json` kelima invariannya true.
Console warning/error, exception JS, HTTP lokal gagal, browser Groq langsung,
asset duplication, overflow dan layout shift dipantau. Tidak ada event tak terduga
pada matriks akhir; 12 aset Vite/favicon juga HTTP 200 (`assets.json`).

Tidak ada klaim menggunakan DevTools MCP, audit unused CSS menyeluruh atau semua
rule vendor terpakai. Dead selector dihapus atas bukti referensi source, bukan
coverage satu halaman. CSS Bootstrap entry landing/dashboard tetap relatif besar
(~299/~303 kB sebelum gzip, ~45 kB gzip); optimasi framework/bundle terpisah belum
dilakukan karena berisiko mengubah perilaku existing tanpa kebutuhan audit ini.

## 24. Groq tanpa request tambahan — PASS

Historis yang dipertahankan: HTTP 200, **1 request live**, **526 token**, analisis
**ID 5**, tiga rekomendasi; hasil simulasi sebelumnya ID 4 tetap terpisah.
Verifikasi baru saved-result: Hendrik/Owner melihat penjelasan dan rekomendasi yang
sama, refresh tidak memanggil provider, empty semester aman, role lain dibatasi,
label live/simulasi berbeda dan mode live nonaktif. Browser memeriksa pola key
pada HTML publik tanpa membaca/mencocokkan nilai key asli; tidak ada marker yang
terdeteksi dan tidak ada request browser ke `api.groq.com`.

Backend reuse memakai HTTP fake yang menolak request provider dan `assertNothingSent`:
`same_analysis_id=true`, `provider_calls=0`, `transport=live`, `live_enabled=false`,
`persistent_attempts=1`. Ledger dan `.env` identik dengan hash opaque sebelum audit;
tidak mengekstrak GROQ_API_KEY, tidak menjalankan ulang smoke live. Deskriptif tetap
tanpa skor/kategori risiko resmi; klasifikasi **NEEDS SPECIFICATION**.

## 25. Regression tests — PASS

| Pemeriksaan baru | Hasil |
| --- | --- |
| PHPUnit SQLite `:memory:` | **139 tes / 2.356 assertions PASS**, 17,509 detik |
| Authentication/authorization, KRS, finalisasi, dashboard, risiko, mocked Groq/reuse | Termasuk suite di atas; assertions tidak dilemahkan |
| Laravel Pint `--test` | **136 file PASS** |
| PHP syntax source/tes/scripts/lang | **137 file PASS** |
| Vite build final, npm WSL | **PASS**, 128 modul, 2,86 detik; manifest/aset terbangun |
| Aset Vite + favicon HTTP | **12 PASS**, tidak ada secret-format marker |
| Browser HTTP saved-result | **7/7**, sesuai expected status termasuk larangan role/validasi |
| `git diff --check` dan cached whitespace | PASS; index tidak diubah |
| Integritas 15 tabel akademik, `.env`, ledger | **Identik**; program QA tersisa 0 |

Log baru `phpunit.log`, `pint.log`, `php-syntax.log`, `build.log` dan
`integrity-final.json` berada dalam direktori artifacts ignored. Hash akademik
membandingkan baris terurut sebelum/sesudah, bukan byte SQLite yang bisa berubah
karena session/sequence. Browser login/session/cache development dapat berubah;
baris akademik existing tetap sama. Tidak ada migration/seeder atau tes MySQL pada
sesi ini. Baseline MySQL dari laporan lama **tidak** dinyatakan sebagai tes baru.

## 26. Remaining issues

- **BLOCKED:** kedua MCP tidak callable pada sesi ini; memerlukan sesi/tool exposure
  yang sesuai untuk hasil MCP nyata. Tidak ada konfigurasi global yang diubah.
- **PARTIAL:** Chrome lokal saja; Safari/Firefox, perangkat fisik, native zoom semua
  halaman, screen reader dan aksesibilitas menyeluruh belum diuji.
- **NEEDS SPECIFICATION:** klasifikasi/scoring risiko resmi dan kebijakan akademik
  yang belum ditetapkan tetap tidak diada-adakan oleh styling.
- **PARTIAL:** optimasi bundle vendor, production/load/hosting dan MySQL QA baru
  di luar pengujian desain ini. Tidak ada klaim siap deployment.

Tidak ada temuan produk baru yang masih gagal pada matriks dan interaksi yang
dijalankan. Perbedaan purposeful hero/auth/dashboard, pagination ringkas dan warna
ilustrasi branding tetap ada, bukan desain seragam setiap elemen secara harfiah.

## 27. Final verdict

**PARTIAL — implementasi konsistensi desain dan QA alternatif PASS; Playwright MCP
serta Chrome DevTools MCP BLOCKED.** Perbaikan berupa source CSS/Blade/Chart nyata,
bukan dokumentasi saja. Identitas, workflow, hak akses, data akademik dan hasil
Groq tersimpan dipertahankan. `.env`/ledger tetap identik, tanpa request live baru,
akses database operasional, commit, push atau deployment.
