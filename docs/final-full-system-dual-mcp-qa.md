# Final full-system dual-MCP QA UniNalar

> Path `/path/to/UniNalar-main`, `~`, `<windows-user-home>` dan `<runtime-tag>`
> adalah placeholder publik pengganti identitas mesin pada bukti lokal. Ganti sesuai
> workspace/runtime development yang sudah diverifikasi sebelum memakai contoh perintah.

8 November 2026, Asia/Jakarta. Workspace `/path/to/UniNalar-main`, URL `http://localhost:8140`.

**Playwright MCP PASS; Chrome DevTools MCP PASS.** Inventaris 97 route / 60 halaman; matriks utama **300/300 render HTTP 200**, ditambah **70 render retest terarah**. Regresi akhir **139 tes / 2.356 assertions PASS**. Tiga refinement Blade diterapkan dan diuji ulang. Audit menyeluruh tetap **PARTIAL** untuk seluruh kemungkinan state, aksesibilitas, keamanan, perangkat dan performa produksi; batasnya dijelaskan di bawah.

Hasil laporan lama tidak dihitung sebagai bukti baru. Khususnya hasil Playwright library/CDP pada `final-design-system-mcp-audit.md` dan status MCP terhambat pada `final-dual-mcp-system-audit.md` bersifat historis. Sesi ini memakai kedua MCP yang callable secara langsung.

## 1. MCP invocation evidence

| Tool yang benar-benar dipanggil | Hasil sesi ini |
| --- | --- |
| `mcp__playwright__browser_tabs` | PASS, memakai tab QA existing; satu tab pada akhir audit |
| `mcp__playwright__browser_navigate` | PASS, publik, halaman privat dan cleanup |
| `mcp__playwright__browser_snapshot` | PASS, antara lain login, KRS, nilai, form Admin dan dialog |
| `mcp__playwright__browser_resize` | PASS, ukuran desktop; matriks memakai kemampuan viewport pada tool MCP di bawah |
| `mcp__playwright__browser_click` | PASS, KRS, validasi, bantuan, logout dan konfirmasi CRUD |
| `mcp__playwright__browser_fill_form` | PASS, pemilihan kelas QA pada KRS |
| `mcp__playwright__browser_press_key` | PASS, Tab, Shift+Tab, Enter, Escape |
| `mcp__playwright__browser_evaluate` | PASS, validasi native, fokus, dialog dan readback cancel |
| `mcp__playwright__browser_run_code_unsafe` | PASS, matriks, login/logout, filter, sidebar dan workflow; lihat batas tooling di bagian 18 |
| `mcp__playwright__browser_take_screenshot` | PASS, screenshot tersimpan di `.playwright-mcp/` |
| `mcp__playwright__browser_handle_dialog` | PASS pada retest cancel/accept; percobaan awal bertabrakan dengan listener harness |
| `mcp__chrome_devtools__list_pages` | PASS, tab 1 blank dan tab 2 aplikasi existing dipertahankan |
| `mcp__chrome_devtools__navigate_page` | PASS, lintas role dan halaman publik |
| `mcp__chrome_devtools__take_snapshot` | PASS, daftar/detail review KRS |
| `mcp__chrome_devtools__emulate` | PASS, viewport mobile 375×812/touch dan desktop 1366×768, reduced motion |
| `mcp__chrome_devtools__evaluate_script` | PASS, computed CSS, fokus, identitas, login dan form QA melalui DOM |
| `mcp__chrome_devtools__list_console_messages` | PASS, lintas empat role; satu issue autocomplete ditemukan lalu diperbaiki |
| `mcp__chrome_devtools__list_network_requests` | PASS, aset/dokumen lintas role HTTP 200 |
| `mcp__chrome_devtools__click`, `mcp__chrome_devtools__fill_form` | Tool mengembalikan sukses, tetapi perubahan pada review KRS mobile tidak teramati; tidak dihitung sebagai keputusan berhasil |
| `mcp__chrome_devtools__performance_start_trace` | PASS dengan `reload:true, autoStop:true`, setelah percobaan ekspor path ditolak |

`browser_run_code_unsafe` adalah tool resmi Playwright MCP yang menerima objek `page` milik sesi MCP. Matriks dan interaksi menggunakan objek tersebut. Tidak meluncurkan browser/library lain, tidak memasang dependency, dan tidak menggunakan CDP langsung. DevTools juga tetap melalui tool MCP-nya; keputusan KRS akhirnya memakai DOM `requestSubmit()` lewat `evaluate_script`, lalu hasilnya dibuktikan pada role lain dan database.

Bukti terstruktur baru di direktori ignored `qa-artifacts/full-system-dual-mcp/`: `routes.json`, `pages.json`, `browser-matrix.json`, `targeted-retest.json`, `interactions-final.json`, `delete-checks.json`, `audit-summary.json`, `workflow-browser.json`, `workflow-database.json`, `devtools-role-inspections.json`, `reuse-summary.json`, `css-source.json`, `contrast.json`, `after-integrity.json` dan log regresi. Snapshot/screenshot berada di `.playwright-mcp/`. Tidak ada password, cookie, token session atau API key dicetak dalam laporan/artifact hasil browser ini.

## 2. Workspace dan isolation

Tidak ada `AGENTS.md` yang ditemukan pada pencarian direktori proyek/parent. Pedoman UI/UX proyek, matriks desain, laporan audit sebelumnya, dokumentasi fixture autentikasi dan Groq dibaca. Skill lokal UI/UX Pro Max digunakan sebagai referensi checklist; pencarian dataset UX mencakup keyboard focus dan feedback form.

PHP native tidak tersedia. Runtime development existing yang diverifikasi adalah `uninalar-wsl-dev-<runtime-tag>`: working directory dan bind source sama dengan workspace WSL, port `8000` hanya dipublikasikan sebagai `127.0.0.1:8140`. Hanya inspect/exec runtime development itu yang digunakan; tidak membuat/mengubah container, network, volume atau Compose. Akses socket awal ditolak oleh sandbox; eksekusi read-only/QA kemudian diizinkan melalui mekanisme escalation tool, tanpa sudo.

`WslDevelopmentIdentity::verify()` benar-benar dijalankan: environment `local`, driver aktual SQLite, path/manifest/PRAGMA cocok, live Groq `false`. Pemeriksaan Python tambahan membuka file SQLite dengan `mode=ro`, memverifikasi path dan bukan symlink, lalu membuat snapshot konsisten `before.sqlite` (0600) dan hash baris terurut sebelum mutasi.

Seluruh perubahan lokal existing dipertahankan. Tidak membuka `.env` untuk mencetak nilainya; hanya membandingkan hash opaque. Tidak mengakses MySQL operasional, source Windows, Docker operasional, provider Groq live, commit, push atau deployment. Konfigurasi MCP/browser tidak diubah. Browser Linux QA existing dan profil terisolasi tetap digunakan; sesi startup sebelumnya sudah membuktikan launcher QA Linux, dan browser mengembalikan `Linux x86_64 / HeadlessChrome/157.0.0.0`.

## 3. Route/page coverage

Route list Laravel aktual disimpan dari runtime development: **97 route**, tanpa perubahan dari baseline. Setelah mengeluarkan alias/redirect, healthcheck, storage, dan endpoint mutatif, terdapat **60 template halaman HTML**: publik 2, Mahasiswa 8, Dosen 7, Admin 27, Owner 16. Parameter diisi ID fixture SQLite yang sah. Halaman KRS mencakup riwayat; IPS/IPK berada pada ringkasan/nilai, bukan route tambahan yang direka.

| Kelompok | Halaman unik | Render lima viewport | Hasil |
| --- | ---: | ---: | --- |
| Publik | 2 | 10 | HTTP 200; DOM PASS |
| Mahasiswa | 8 | 40 | HTTP 200; DOM PASS |
| Dosen | 7 | 35 | HTTP 200; DOM PASS |
| Admin Akademik | 27 | 135 | HTTP 200; DOM PASS |
| Owner/Kaprodi | 16 | 80 | HTTP 200; DOM PASS |
| Total utama | 60 | 300 | 300/300 |

Matriks per halaman pada akhir laporan menunjukkan URL parameter konkret dan interaksi yang benar-benar diuji. Render halaman detail/edit existing tidak berarti mutasi pada record existing; mutasi browser memakai record QA baru pada template yang sama.

## 4. Responsive coverage

Kelima viewport dipakai tepat: **375×812, 390×844, 768×1024, 1366×768, 1920×1080**. Pada setiap 300 render, status dokumen, dimensi viewport, overflow dokumen, label kontrol terlihat, ID duplikat, referensi ARIA, gambar gagal, font, breadcrumb dan computed styles dicatat. Semuanya HTTP 200, tanpa overflow horizontal dokumen, label hilang, ARIA putus, ID duplikat, gambar gagal atau marker key yang diperiksa.

Retest terarah: 12 halaman create/edit enam master + landing = **65 render/screenshot**; KRS lima viewport = **5 render DOM tambahan**. Jadi **370 render matriks/retest**, bukan 370 halaman unik atau workflow. Sebanyak 300 screenshot utama, 65 screenshot retest, enam screenshot validasi dan dua screenshot mobile role dibuat. Tidak menyatakan setiap piksel seluruh screenshot diperiksa manual; tinjauan visual langsung mencakup login, dashboard Mahasiswa/Dosen mobile, form panjang Mahasiswa mobile dan Owner desktop.

Navigasi mobile dashboard memang berbentuk grid menu yang terlihat, bukan drawer yang diasumsikan ada. Tautan mobile diklik pada seluruh empat role. Landing memakai collapse Bootstrap; open → klik Fitur → collapse tertutup → hash dan posisi scroll akhir diverifikasi setelah transisi selesai. Tabel lebar tetap berada dalam region scroll; chart Mahasiswa dan form panjang tampil pada matriks. Native browser zoom seluruh halaman dan perangkat fisik belum diuji.

## 5. Browser interaction coverage

`interactions-final.json` menyimpan **107 pemeriksaan interaksi awal**: 105 berhasil, dua gagal awal pada probe landing. Kegagalan feature-link adalah pembacaan terlalu dini sebelum animasi selesai; retest lulus. Skip-link hanya menggulir tanpa memindahkan `activeElement` ke main; target diperbaiki dan retest lulus. Delapan pemeriksaan retest/aksesibilitas tambahan dicatat terpisah. Hasil awal tidak dihapus atau disajikan sebagai PASS sejak awal.

Di luar angka itu terdapat bukti khusus enam skenario cancel/delete pada `delete-checks.json`, workflow lintas role, pemeriksaan Groq, dan login/logout. Tidak menjumlahkan semuanya menjadi workflow unik. Pada percobaan awal delete, listener dialog harness bertabrakan dengan penanganan MCP; class/student dibersihkan tetapi cancel belum terbukti. Retest dengan dua record QA baru dan tool `browser_handle_dialog` langsung berhasil membuktikan cancel (record tetap ada), accept dan cleanup. Keempat master lainnya juga membuktikan cancel/accept.

Teruji: enam create/detail, enam edit/readback, enam cancel edit, enam search empty/reset, enam validasi server, pagination Admin/Owner, 16 submit filter Owner, lima filter Mahasiswa, lima submit filter Dosen, sidebar/breadcrumb, keyboard/modal, pemilihan/pengajuan KRS dan nilai. Login/logout empat role dilakukan memakai akun fixture; setelah logout, akses privat Mahasiswa kembali ke login. Rendering profil diuji; seluruh variasi perubahan kontak profil tidak dimutasi lewat browser.

## 6. Functional E2E

**Dua alur akademik lintas role selesai melalui browser MCP**, ditambah enam skenario CRUD master dan autentikasi empat role. Provisioning hanya membuat dua akun QA baru dengan password acak privat; profil, program, course, semester dan class dibuat lewat UI Admin. Semua record memakai penanda `MCPQA`, registry ID, dan guard identity sebelum cleanup.

1. **KRS:** Mahasiswa QA memilih kelas QA pada semester aktif yang window-nya memang terbuka → draft → ajukan versi 1 → Admin membaca snapshot dan menolak dengan catatan → Mahasiswa melihat penolakan, mengajukan versi 2 → Admin menyetujui → Mahasiswa melihat approved → Dosen melihat peserta → Owner melihat student/class/KRS. Tidak mengubah window/semester existing. Database membuktikan urutan histori `ditolak, disetujui`.
2. **Nilai:** Dosen QA menginput **85/A**, menyimpan draft, lalu finalisasi → kontrol save/finalisasi hilang setelah final → Mahasiswa QA melihat **85.00/A/Final**, **IPS 4.00, IPK 4.00**, satu nilai final valid → Owner melihat mahasiswa/nilai yang sama. Nilai final fixture utama tidak diedit. Database membuktikan grade final QA sebelum cleanup.

Nilai draft tidak masuk IPS/IPK dan finalisasi berulang/lintas dosen dilindungi regresi SQLite. Browser membuktikan status draft sebelum finalisasi dan hasil final setelahnya; tidak mengklaim semua variasi angka/bobot/konkurensi dijalankan lewat UI. Perhitungan dan kebijakan existing dipakai, tanpa formula atau klasifikasi risiko baru.

## 7. Audit CSS/design system

Enam source CSS diperiksa: `design-tokens.css`, `landing.css`, `login.css`, `student-dashboard.css`, `admin-academic.css`, `app.css`. Parsing PostCSS existing mencatat **528 rule**, **0 exact duplicate rule pada scope yang sama**, dan empat `!important` pada landing untuk reduced motion. Ini bukan bukti semua selector vendor terpakai atau seluruh deklarasi serupa aman dihapus.

Token existing dipertahankan: primary `#2563eb`, hover `#1d4ed8`, secondary `#334155`, background `#f8fafc`, surface putih, text `#14213d`, border `#cbd5e1`. Computed body lintas empat role: **Plus Jakarta Sans / 14px**, text `rgb(20,33,61)`, background `rgb(248,250,252)`; judul dashboard **26px**. Form text/select/button akademik yang disampel **44px** tinggi, border input `rgb(203,213,225)`, primary button `rgb(37,99,235)`; checkbox tetap kontrol kecil dengan label. Header tabel memakai latar `rgb(241,245,249)`, caption 12px. Font `loaded`; breadcrumb berada sebelum judul dan satu sidebar item aktif pada matriks dashboard.

Bootstrap berada pada layer yang didefinisikan existing, Tailwind theme/utilities pada layer tersendiri. Tidak ditemukan konflik rendering pada matriks; tidak melakukan purge vendor atau redesign. Landing hero, login, status badge dan chart mempertahankan variasi fungsionalnya. Tidak ada perubahan CSS sesi ini.

Tujuh pasangan token teks yang diukur: body/white **15.97**, muted/white **6.29**, white/primary **5.17**, secondary/white **10.35**, success/background **4.80**, warning/background **4.93**, danger/background **5.93**. Ini mencakup pasangan tersebut saja, bukan semua state/overlay/warna chart.

## 8. Mahasiswa

Delapan halaman / 40 render utama, plus KRS lima retest. Dashboard, profil, KRS/riwayat, nilai/IPS/IPK, kehadiran, analisis, rekomendasi dan tindak lanjut terbuka. Sidebar delapan tautan diklik pada mobile. Lima filter utama disubmit. Chart terender; data alternatif dan empty state tetap tersedia sesuai implementasi.

Hendrik tetap **IPS 3.63 / IPK 3.26**, dengan 14 nilai final akademik fixture-nya; draft utama tidak disentuh. Nilai QA 4.00/4.00 milik mahasiswa QA lain dan telah dibersihkan. Akses Mahasiswa ke dashboard Dosen/Admin/Owner menghasilkan **403**. Tidak ada request provider dari navigasi/refresh hasil tersimpan.

## 9. Dosen

Tujuh halaman / 35 render. Kelas, detail, peserta, kehadiran, nilai dan profil terbuka dengan scope akun. Empat tautan sidebar mobile dan lima submit filter diuji. Workflow draft/finalisasi menggunakan akun/profil/kelas QA sendiri. Akses kelas fixture milik dosen lain menghasilkan **404**, sedangkan dashboard tiga role lain menghasilkan **403**. Nilai final Jovian existing tetap utuh.

Input/koreksi kehadiran atau kebijakan konversi angka ke huruf yang belum diimplementasikan tidak ditambahkan atau diklaim teruji sebagai fitur baru.

## 10. Admin Akademik

27 halaman / 135 render utama; seluruh 12 form create/edit diuji ulang pada lima viewport sesudah refinement autocomplete. Enam master menjalani create/detail, edit/readback, cancel, search empty/reset, validasi server dan delete record QA. Validasi native form kosong memfokuskan field kode; validasi server keenam form menampilkan summary yang difokuskan dan masing-masing field error terasosiasi. Pagination kelas berhasil ke halaman 2.

Review KRS dilakukan pada versi QA 1 dan 2. Snapshot sebelum keputusan terlihat; status/catatan terbaca oleh Mahasiswa. Delapan tautan sidebar mobile diklik. Dashboard role lain ditolak **403**. Form untuk mengelola data orang lain sekarang memakai `autocomplete="off"` pada input/select, agar browser tidak mengisi data akademik dengan identitas pribadi operator.

## 11. Owner/Kaprodi

16 halaman / 80 render; 16 form GET filter disubmit, 11 tautan sidebar mobile dan breadcrumb diklik, pagination kehadiran berhasil ke halaman 2. Monitoring student/class/KRS/grade QA sesuai alur sumber; main content sampel tersebut tidak memuat POST form. Dashboard Admin ditolak **403**.

Detail analisis ID 5 memperlihatkan penjelasan dan label live yang sama dengan Mahasiswa. Ketiga rekomendasi diperiksa pada halaman rekomendasi Owner, bukan diasumsikan berada pada detail analisis. Probe awal yang memakai halaman detail untuk mencari rekomendasi menghasilkan false; pemeriksaan pada halaman yang benar lulus. Owner tetap read-only; perlindungan endpoint mutatif lebih luas tercakup suite authorization SQLite.

## 12. Groq saved-result

**Request live baru: 0.** Web `live_enabled=false`. Analisis Hendrik ID 5, label **Hasil live Groq tersimpan**, tiga rekomendasi, timestamp 08/11/2026 19:50 WIB dan agregat tersimpan terlihat. Riwayat simulasi tetap berlabel simulasi. Refresh halaman menghasilkan teks sama. Owner menampilkan penjelasan dan rekomendasi yang sesuai; skor/kategori resmi tetap tidak ditetapkan.

Probe backend runtime baru memakai `Http::preventStrayRequests()`, HTTP fake yang melempar bila provider dipanggil, dan `assertNothingSent()`: **same_analysis_id=true, provider_calls=0, transport=live, live_enabled=false, persistent_attempts=1**. Hash ledger unchanged. HTTP integration success/error/rate-limit/reuse diuji dengan fake pada suite PHPUnit, bukan provider nyata.

Tidak ditemukan request browser ke `api.groq.com` pada matriks yang dipantau atau pola key `gsk_…`/`sk-proj-…` pada DOM 300 render. Ini bukan pemindaian semua jenis secret atau bukti nol server egress hanya dari network browser; guard runtime/fake/ledger menyediakan bukti tambahan.

## 13. Accessibility

Pemeriksaan label, ARIA reference, ID, gambar, breadcrumb dan fokus dilakukan sesuai cakupan di atas. Dialog bantuan login: Tab tetap di dialog, fokus solid **3px**, Escape menutup dan fokus kembali ke pemicu. Toggle password berukuran **44×44**, dapat diaktifkan lewat Enter dan dikembalikan menjadi password. Skip link dashboard Mahasiswa/Owner memfokuskan main. Skip link landing kini juga memfokuskan main setelah refinement `tabindex="-1"`.

Status akademik menampilkan teks, bukan warna saja. CSS reduced motion diperiksa dan DevTools menjalankan emulasi `reduce`. Tidak ada sertifikasi WCAG: screen reader nyata, seluruh tab sequence setiap form, semua kombinasi status/hover, native zoom, dan seluruh target kecil belum diverifikasi menyeluruh. Delapan/11 menu mobile yang diklik bukan klaim seluruh tombol aplikasi diuji keyboard.

## 14. Console/network dan performance

Matriks utama dan 65 retest form/landing: **0 console warning/error atau JavaScript exception yang teramati**. Listener network matriks role privat juga tidak menemukan respons HTTP gagal atau request frontend ke Groq. Status dokumen seluruh 300 utama dan 65 retest adalah 200. Login/logout dan uji akses yang sengaja ditolak dicatat terpisah dari render normal; 403/404 pada probe security adalah expected.

Empat inspeksi DevTools tersimpan mencakup analisis Mahasiswa, nilai Dosen, create Mahasiswa Admin, dan detail analisis Owner: total **28 request** pada empat snapshot network itu, seluruhnya **HTTP 200**. Satu issue non-error autocomplete pada Admin ditemukan; setelah perbaikan, inspeksi ulang tujuh request HTTP 200 dan console kosong. Sampel lain (dashboard, login, KRS, landing) juga diperiksa. Bukan klaim DevTools memeriksa network pada seluruh 370 render secara terpisah.

Trace DevTools pada detail keputusan KRS Admin, mobile/reduced motion, CPU 1×/network tanpa throttling: **LCP 181 ms, TTFB 38 ms, CLS 0.00**. Ini satu pengukuran lab localhost. Insight menyebut peluang image delivery sekitar **1.1 MB**, cache sekitar 1.5 MB, dan respons dokumen tanpa compression sekitar 5.5 kB. Optimasi aset/hosting belum diterapkan: tidak mengubah kualitas/logo branding atau konfigurasi server selama refinement tiga Blade. Angka ini bukan Core Web Vitals lapangan atau audit beban produksi.

## 15. Bugs found and fixed

| ID | Reproduksi / expected vs actual | Root cause dan perbaikan | Bukti retest |
| --- | --- | --- | --- |
| QA-01 | Pilih seluruh kelas yang tersedia pada draft KRS. Expected: tidak ada tambahan; actual: katalog disebut belum tercatat walaupun tabel pilihan berisi kelas. | `MahasiswaKrsController` memfilter kelas terpilih dari available; view memakai satu empty state. `resources/views/dashboard/mahasiswa/krs.blade.php` kini membedakan KRS berisi pilihan dari katalog kosong. | Playwright pada status ditolak: pesan baru true. DevTools pada draft: pesan baru true, pesan lama false, display flex, console kosong dan delapan request 200. Lima retest viewport KRS PASS; workflow resubmit/approval tetap berhasil. |
| QA-02 | DevTools membuka create Mahasiswa Admin: issue missing autocomplete. | Template bersama belum mengatur autocomplete. `resources/views/dashboard/admin-akademik/resource-form.blade.php` menambahkan off pada input/select untuk entry data pihak lain. | DevTools: delapan field off, issue hilang, tujuh request 200. Seluruh 12 create/edit × lima viewport PASS. Validasi server enam form sebelumnya lulus; perubahan hanya atribut autocomplete. |
| QA-03 | Keyboard aktifkan skip link landing: halaman menggulir, `activeElement` tetap BODY. Expected: fokus masuk main. | Target main tidak focusable. `resources/views/landing/index.blade.php` menambahkan `tabindex="-1"`. | Playwright Enter: focus pada konten true; DevTools: targetFocusable true/tabindex -1. Landing lima viewport PASS; navbar collapse/Fitur retest PASS. |

Perubahan sesi ini hanya tiga refinement Blade di atas dan laporan ini (di luar artifact ignored). Tidak menghapus perubahan lama pada file-file yang sama. Tidak ada redesign, perubahan kebijakan akademik atau pengaktifan AI.

## 16. PHPUnit/build/Pint

| Pemeriksaan sesi ini | Hasil |
| --- | --- |
| PHPUnit akhir, SQLite `:memory:` | **139 tes / 2.356 assertions**, 21.892 detik, PASS |
| Authentication/authorization, KRS, finalisasi, CRUD, published summary, Groq fake/reuse | Termasuk suite penuh; tidak melemahkan assertion existing |
| PHP syntax source/test/database/scripts/lang | **121 file PASS**, `php-syntax.log` |
| Laravel Pint `--test` | **136 file PASS**, `pint.log` |
| Vite build akhir | **PASS**, 4.22 detik, `build-final.log` |
| Git diff whitespace | `git diff --check` PASS; tidak staging/commit |
| MySQL QA | NOT RUN; database operasional tidak digunakan |

Suite dijalankan pada environment testing dengan SQLite memory, DB URL kosong dan live Groq false. Tidak menjalankan migration/seeder terhadap SQLite development sebagai bagian regresi. Browser memang menulis fixture QA miliknya melalui UI, dengan provisioning/cleanup guarded terpisah.

## 17. Integritas data dan session hygiene

Sebelum/sesudah, **14 tabel akademik identik per baris terurut**: program studi 2, semester 3, mata kuliah 16, dosen 2, mahasiswa 3, kelas 16, KRS 4, detail 16, histori pengajuan 3, nilai 16, kehadiran 120, analisis 5, rekomendasi 10, tindak lanjut 0. Foreign-key violations **0**; penanda record QA tersisa **0**. Dua akun QA baru dihapus setelah profilnya dihapus; users kembali **9**.

`users` tidak byte-identik: hanya `remember_token` pada dua akun berubah akibat logout Laravel. Password hash, username, role, profil dan kolom lain tetap identik. Tidak memulihkan token lama karena rotasi session adalah perilaku logout yang benar. `.env` dan ledger Groq identik; ledger tetap satu attempt historis. Tidak restore database penuh atau mengubah data utama untuk menyembunyikan efek tes.

Snapshot/hashes dan bukti workflow disimpan di artifact ignored. File plaintext credential dua akun QA buatan sesi sudah dihapus setelah akun dibersihkan. Credential fixture existing tidak diubah/dihapus. Browser tidak menambah tab baru pada audit ini: satu tab Playwright dan dua tab DevTools existing dipertahankan. Akun logout pada akhir audit. Tidak menutup tab pengguna lain.

## 18. Remaining issues dan batas tooling

- **PARTIAL:** belum menguji setiap kombinasi form/filter, seluruh validation/error/loading state di setiap halaman/viewport, seluruh aksi kontak profil, atau seluruh kemungkinan workflow/konkurensi melalui UI. Suite backend melengkapi, bukan menggantikan bukti browser.
- **PARTIAL:** Chrome Linux headless saja; tidak ada Safari/Firefox/perangkat fisik/screen reader/native zoom menyeluruh/pentest/load test. Pemeriksaan pola secret bukan audit semua response byte atau semua tipe kebocoran.
- **OPEN, non-critical:** peluang optimasi ukuran gambar, cache/compression dan bundle vendor. Insight satu trace belum menjadi bukti perlambatan produksi. Tidak ada error kritis produk yang tersisa pada kasus yang diuji.
- **NEEDS SPECIFICATION:** skoring/klasifikasi risiko resmi, kebijakan yang belum diimplementasikan, input/koreksi kehadiran dan workflow tindak lanjut yang belum tersedia tetap tidak direka.
- **NOT VERIFIED:** MySQL QA/migration akun nyata, topologi produksi dan keamanan menyeluruh. Tidak ada izin/percobaan live Groq baru.
- **Tooling:** dua percobaan `require`/dynamic import pada sandbox run-code ditolak; tidak digunakan untuk matriks. Data login diteruskan secara privat dari filesystem lokal melalui orchestration, hasil credential tidak dicetak. Percobaan ekspor trace ke path workspace ditolak oleh allowlist DevTools; trace tanpa ekspor berhasil, metrik dicatat dari respons tool. Percobaan click/fill DevTools mobile tidak menghasilkan perubahan yang teramati; aksi berhasil dibuktikan lewat evaluate_script MCP dan readback lintas role. Listener dialog custom sempat bertabrakan dengan MCP; retest resmi berhasil. Tidak mengubah konfigurasi untuk mengatasi batas ini.

## 19. Final verdict

**PASS: Playwright MCP, Chrome DevTools MCP, inventaris/render 60 halaman × lima viewport, retest 70 render, dua alur akademik QA, enam CRUD QA, autentikasi empat role, regresi SQLite/build/Pint/syntax, reuse tanpa provider dan integritas data akademik.**

**PARTIAL untuk klaim full-system tanpa batas:** seluruh state/perangkat, aksesibilitas/keamanan menyeluruh dan performa produksi belum terbukti. Tidak ada klaim sertifikasi WCAG atau persetujuan deployment. Tiga masalah yang direproduksi telah diperbaiki; local changes pengguna dipertahankan. Tidak ada commit, push atau deploy.

## Lampiran — coverage per halaman

Setiap baris memiliki lima render HTTP 200, DOM/computed CSS dan screenshot pada matriks utama. Kolom interaksi membedakan tindakan tambahan dari render. Detail record existing tidak dimutasi; CRUD memakai record QA baru pada template terkait. Bukti numerik lengkap ada pada `browser-matrix.json` dan `interactions-final.json`.

| Role | Route / URL fixture | Render | Interaksi tambahan sesi ini |
| --- | --- | --- | --- |
| public | `/` → `/` | 5/5 · 200 | Collapse/Fitur/skip link keyboard |
| admin-akademik | `dashboard/admin-akademik` → `/dashboard/admin-akademik` | 5/5 · 200 | Sidebar mobile dan gate lintas role |
| admin-akademik | `dashboard/admin-akademik/dosen` → `/dashboard/admin-akademik/dosen` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/dosen/create` → `/dashboard/admin-akademik/dosen/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/dosen/{record}` → `/dashboard/admin-akademik/dosen/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/dosen/{record}/edit` → `/dashboard/admin-akademik/dosen/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/kelas-kuliah` → `/dashboard/admin-akademik/kelas-kuliah` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/kelas-kuliah/create` → `/dashboard/admin-akademik/kelas-kuliah/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah}` → `/dashboard/admin-akademik/kelas-kuliah/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/kelas-kuliah/{kelas_kuliah}/edit` → `/dashboard/admin-akademik/kelas-kuliah/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/krs-pengajuan` → `/dashboard/admin-akademik/krs-pengajuan` | 5/5 · 200 | Daftar/detail dan keputusan QA via DevTools MCP; readback lintas role |
| admin-akademik | `dashboard/admin-akademik/krs-pengajuan/{history}` → `/dashboard/admin-akademik/krs-pengajuan/1` | 5/5 · 200 | Daftar/detail dan keputusan QA via DevTools MCP; readback lintas role |
| admin-akademik | `dashboard/admin-akademik/mahasiswa` → `/dashboard/admin-akademik/mahasiswa` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/mahasiswa/create` → `/dashboard/admin-akademik/mahasiswa/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/mahasiswa/{record}` → `/dashboard/admin-akademik/mahasiswa/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/mahasiswa/{record}/edit` → `/dashboard/admin-akademik/mahasiswa/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/mata-kuliah` → `/dashboard/admin-akademik/mata-kuliah` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/mata-kuliah/create` → `/dashboard/admin-akademik/mata-kuliah/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/mata-kuliah/{record}` → `/dashboard/admin-akademik/mata-kuliah/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/mata-kuliah/{record}/edit` → `/dashboard/admin-akademik/mata-kuliah/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/program-studi` → `/dashboard/admin-akademik/program-studi` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/program-studi/create` → `/dashboard/admin-akademik/program-studi/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/program-studi/{record}` → `/dashboard/admin-akademik/program-studi/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/program-studi/{record}/edit` → `/dashboard/admin-akademik/program-studi/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/semester` → `/dashboard/admin-akademik/semester` | 5/5 · 200 | Search empty/reset; cancel/delete QA; sidebar; pagination kelas |
| admin-akademik | `dashboard/admin-akademik/semester/create` → `/dashboard/admin-akademik/semester/create` | 5/5 · 200 | Create QA; validasi native/server; focus summary; R5 retest autocomplete |
| admin-akademik | `dashboard/admin-akademik/semester/{record}` → `/dashboard/admin-akademik/semester/1` | 5/5 · 200 | Detail QA setelah create/edit |
| admin-akademik | `dashboard/admin-akademik/semester/{record}/edit` → `/dashboard/admin-akademik/semester/1/edit` | 5/5 · 200 | Edit/readback QA; cancel; R5 retest autocomplete |
| dosen | `dashboard/dosen` → `/dashboard/dosen` | 5/5 · 200 | Sidebar menu utama / navigasi halaman |
| dosen | `dashboard/dosen/kelas` → `/dashboard/dosen/kelas` | 5/5 · 200 | Sidebar menu utama / navigasi halaman; filter GET |
| dosen | `dashboard/dosen/kelas/{kelas}` → `/dashboard/dosen/kelas/2` | 5/5 · 200 | Sidebar menu utama / navigasi halaman |
| dosen | `dashboard/dosen/kelas/{kelas}/kehadiran` → `/dashboard/dosen/kelas/2/kehadiran` | 5/5 · 200 | Sidebar menu utama / navigasi halaman; filter GET |
| dosen | `dashboard/dosen/kelas/{kelas}/peserta` → `/dashboard/dosen/kelas/2/peserta` | 5/5 · 200 | Sidebar menu utama / navigasi halaman; filter GET; peserta QA setelah approval |
| dosen | `dashboard/dosen/nilai` → `/dashboard/dosen/nilai` | 5/5 · 200 | Sidebar menu utama / navigasi halaman; filter GET; nilai QA draft → final; readback Mahasiswa/Owner |
| dosen | `dashboard/dosen/profil` → `/dashboard/dosen/profil` | 5/5 · 200 | Sidebar menu utama / navigasi halaman; kontak mutatif browser tidak diuji |
| mahasiswa | `dashboard/mahasiswa` → `/dashboard/mahasiswa` | 5/5 · 200 | Sidebar mobile |
| mahasiswa | `dashboard/mahasiswa/analisis-risiko` → `/dashboard/mahasiswa/analisis-risiko` | 5/5 · 200 | Sidebar mobile; filter; saved-result/refresh/reuse; disabled mode live |
| mahasiswa | `dashboard/mahasiswa/kehadiran` → `/dashboard/mahasiswa/kehadiran` | 5/5 · 200 | Sidebar mobile; filter/empty sesuai fixture |
| mahasiswa | `dashboard/mahasiswa/krs` → `/dashboard/mahasiswa/krs` | 5/5 · 200 | Sidebar mobile; pilih kelas → submit → tolak → resubmit → approved; retest empty state |
| mahasiswa | `dashboard/mahasiswa/nilai` → `/dashboard/mahasiswa/nilai` | 5/5 · 200 | Sidebar mobile; filter; hasil final QA/IPS/IPK |
| mahasiswa | `dashboard/mahasiswa/profil` → `/dashboard/mahasiswa/profil` | 5/5 · 200 | Sidebar mobile; kontak mutatif browser tidak diuji |
| mahasiswa | `dashboard/mahasiswa/rekomendasi` → `/dashboard/mahasiswa/rekomendasi` | 5/5 · 200 | Sidebar mobile; filter; tiga rekomendasi live tersimpan |
| mahasiswa | `dashboard/mahasiswa/tindak-lanjut` → `/dashboard/mahasiswa/tindak-lanjut` | 5/5 · 200 | Sidebar mobile; filter/empty sesuai fixture |
| owner | `dashboard/owner` → `/dashboard/owner` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/analisis-risiko` → `/dashboard/owner/analisis-risiko` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/analisis-risiko/{analysis}` → `/dashboard/owner/analisis-risiko/5` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/dosen` → `/dashboard/owner/dosen` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/dosen/{lecturer}` → `/dashboard/owner/dosen/2` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/kehadiran` → `/dashboard/owner/kehadiran` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/kelas` → `/dashboard/owner/kelas` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/kelas/{classRoom}` → `/dashboard/owner/kelas/2` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/krs` → `/dashboard/owner/krs` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/krs/{krs}` → `/dashboard/owner/krs/4` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/laporan` → `/dashboard/owner/laporan` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/mahasiswa` → `/dashboard/owner/mahasiswa` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/mahasiswa/{student}` → `/dashboard/owner/mahasiswa/3` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/nilai` → `/dashboard/owner/nilai` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/rekomendasi` → `/dashboard/owner/rekomendasi` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| owner | `dashboard/owner/tindak-lanjut` → `/dashboard/owner/tindak-lanjut` | 5/5 · 200 | Submit filter GET; sidebar mobile untuk menu utama |
| public | `login` → `/login` | 5/5 · 200 | Login/logout empat role; dialog, toggle password, keyboard |
