# UniNalar — development completion audit

7 November 2026, Asia/Jakarta. Verdict: **PARTIALLY COMPLETE**.
This report separates current execution from historical Phase 1–6 evidence.
Local logs, manifests and disposable harnesses: `qa-artifacts/current/` (ignored).

## Lecturer-module follow-up

See [lecturer development and QA](lecturer-module-completion.md) for subsequent
lecturer pages and current cross-role verification. The original audit counts below
remain a historical snapshot.

## Student-module follow-up

See [the student-module implementation and QA report](student-module-completion.md)
for subsequent student changes and updated results. Counts below are the earlier
audit snapshot, not the latest student-session totals.

## A. Workspace & Git

- Active editing source: `/home/hendra_huang/projects/UniNalar-main`.
- WSL2 kernel: `6.18.33.2-microsoft-standard-WSL2`; Linux filesystem, not `/mnt/c`.
- Branch `main`; HEAD `02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`.
- Remote `https://github.com/Liwaru/UniNalar.git`; working tree initially clean.
- No pull, branch change, commit, push, history rewrite or deployment.
- Old QA source was read only to reuse dependencies and historical assets; it was
  not an editing target. Operational service mounts were not changed.
- Files changed: README.md; AdminAcademicController, AdminKelasKuliahController,
  MahasiswaDashboardController, OwnerDashboardController; two dashboard Blade
  templates; phpunit.xml; AdminFormStateTest, MahasiswaDashboardTest,
  Mysql/AdminMasterTest. Added PublishedAcademicSummaryTest, pint.json,
  this report and shared-hosting-readiness.md.

Inventory: 69 routes, 10 controllers, 1 role middleware, 16 models, 8 services,
7 migrations, 2 seeders, 1 factory, 26 Blade templates, 12 config files and 36 PHP
files under tests (including helpers). No separate Policy or Form Request classes;
current authorization/validation lives in middleware, controllers and services.
Route and file-count evidence is saved locally. Editor/extension integration was
not independently inspected; the supplied Codex cwd and actual tool cwd agree.

## B. Environment

| Component | Current evidence |
| --- | --- |
| PHP | Native PHP unavailable; local Docker image provides PHP 8.2.34, working under UID/GID 1000. |
| Laravel | composer.lock and reused installed dependencies: v12.70.0. |
| Composer | Only a Windows launcher was on host PATH; no Composer installation or Composer scripts were run. Existing QA vendor copied after composer.lock SHA256 equality. Platform check passed. |
| Node / npm | Native Linux Node v24.21.0 / npm 11.19.0. |
| Frontend dependencies | 91 packages installed using npm ci --offline --ignore-scripts --no-audit --no-fund and a copied local cache. No downloads, global install or lifecycle scripts. User also subsequently authorized isolated dependency installation. |
| Docker | Client 29.6.2; sandbox socket denial resolved through approved Docker access. Existing images reused, no pull/build required. |
| Environment | No workspace .env. Public test-only encryption key added to phpunit.xml; disposable MySQL/HTTP keys generated separately in process memory. |
| Runtime directories | storage and bootstrap/cache writable under developer UID. No world-writable permission change. |
| Browser tools | No Playwright MCP or Chrome DevTools MCP available in the tool catalog. Visual QA BLOCKED; actual HTTP checks used as fallback. |

Composer lock SHA256: `a364316d5f1b1eb2e3c0c74cf585b1d5940fee41ddf9dc7db9dcd312131d8ecb`.
Npm lock SHA256: `8094a59b5b09337c9d869fd9a15572a2a936b3936b3792f99f3347e17382d721`.
Both unchanged. Initial feature tests failed because the fresh clone lacked an
APP_KEY; after adding the explicit PHPUnit-only key and supplying existing QA
assets, baseline passed 67 tests / 874 assertions. Those environment failures are
not described as application regressions.

## C. Feature completion

“PARTIAL” below includes missing browser verification; functional test passes are
not a blanket production certification.

| Role | Module / implementation | Current testing | Status / limits |
| --- | --- | --- | --- |
| All | Username login, throttling, role middleware, intended-URL checks, logout/session invalidation | SQLite + MySQL + real HTTP four-role journeys | PASS for exercised functional scope; hosting session/proxy configuration unverified |
| Admin | Program, semester, course, lecturer, student CRUD; search/pagination, uniqueness and protected deletion | Existing feature suite; new malformed-search coverage across six resources | PARTIAL: functional tests pass; browser CRUD matrix not rerun |
| Admin | Class CRUD, lecturer assignment, capacity, related-KRS protection | SQLite/MySQL/concurrency | PARTIAL: functional tests pass; browser verification absent |
| Admin | KRS review/approve/reject and immutable submission versions | SQLite/MySQL and competing decisions | PARTIAL: functional tests pass; operational schema migration not authorized |
| Student | Profile/contact, KRS selection/submission/history/status | Feature tests, MySQL, concurrency, HTTP authorization | PARTIAL: operational migration/browser gates remain |
| Student | Grades, final-only dashboard averages/trends, IPS/IPK | New draft/final/legacy-schema tests, existing ownership/empty-state tests, MySQL integration | PARTIAL: corrected and functionally verified; visual QA absent |
| Student | Attendance | Stored records, configured status aliases and minimum-data empty states tested | PARTIAL: no recording workflow/specification |
| Student | Risk/factors/recommendations/follow-ups | Stored-data isolation, demo exclusion and empty states tested | PARTIAL: read-only stored results; no scoring/processing workflow |
| Lecturer | Assigned classes, approved participants, grade save/edit/finalize/lock | SQLite/MySQL plus concurrent edits/finalization | PARTIAL: functional tests pass; dedicated class-detail/participant navigation not added |
| Lecturer | Attendance entry, final-grade reopening, full grade-edit audit history | No corresponding authorized workflow implementation | NEEDS SPECIFICATION / MISSING |
| Owner | Program/semester filters, KRS/risk/recommendation/follow-up counts, IPS/IPK | Existing tests plus new totals/empty-program/filter tests; MySQL | PARTIAL: real queries; all-history GPA meaning explicitly labelled |
| Owner | Total students/lecturers/programs | Added database-derived totals including programs with no students, excluding demo markers | Functional tests PASS; visual QA absent |
| Owner | Exportable academic report and detailed final-grade distribution | No dedicated export/report workflow | MISSING; report content/access/export requirements need agreement |

Account provisioning remains separate from profile CRUD: profiles can link an
existing account of the matching role; there is no new account/password management
workflow. No inferred academic policy was introduced to fill missing workflows.

## D. Bugs & fixes

| Finding / root cause | Change | Regression evidence |
| --- | --- | --- |
| Student average/trend included draft grades without a draft label; eager-loaded all grades and ignored approval/detail status | Eager-load only final grades when finalization schema exists; aggregate only approved KRS and taken details. Legacy schema produces no published grades. Explicit final labels. Draft grade page retains its existing “Belum final” status. | PublishedAcademicSummaryTest and MySQL AdminMasterTest; related student suite |
| Owner had no total lecturer/program cards; no totals visible when a program had no students | Add filtered database-derived profile/program totals outside the student-dependent section; retain informative empty state | Empty program, demo lecturer exclusion, unassigned lecturer, program/semester filter and invalid-array filter tests |
| Array search input reached string casts; unbounded search lacked validation | Validate nullable string q, max 255, before searching all six Admin resources | 12 invalid-input cases plus valid trimmed searches; MySQL search regression |
| Fresh checkout tests depended on an untracked application key | Explicit public PHPUnit-only key, no environment file edits | Clean-environment SQLite baseline/final suites |
| Pint traversed historical executable QA evidence | Exclude only qa-artifacts via pint.json; preserve archived evidence unchanged | Source Pint passes 99 files; initial unscoped failure retained in local log |

During new-test development, an Eloquent eager-load callback initially assumed a
Builder rather than a relation, and a legacy-schema test attempted to drop an
indexed column before its index. Both were corrected and the affected tests rerun.
No failing tests were removed.

## E. Database

All seven migration sources are inventoried. QA migrations passed on both SQLite
and MySQL, including workflow history and finalization. Operational migration state
was not queried: the pending migration documented in Phase 6 remains historical
information, not a current operational inspection.

SQLite default suite verifies testing/:memory: before RefreshDatabase writes.
MySQL QA used a new MySQL 8.4 container, tmpfs `/var/lib/mysql`, a unique internal
network, no published DB port, and no persistent volume. Before schema creation,
the harness inspected mounts/network, checked actual server UUID/version, verified
absence of schema `uninalar`, and required a fresh `qa_` schema. Existing application
QA guards then checked manifest, container hostname, DB identity and a server-side
marker before migrations/tests/child workers. Only synthetic fixtures were loaded.

The operational UUID was not read in this session; separation is established by
fresh container identity, inspected tmpfs/network and absence of operational mounts.
No operational DB connection, export, backup, migration, seeding or fingerprinting
was performed. Therefore no before/after data-hash equality is claimed.

MySQL constraints and locking passed the existing suite. Seven races exercised
separate worker connections and observed FOR UPDATE. The SHOW STATUS deadlock field
was null/unavailable; this is not evidence of zero deadlocks or a load-test guarantee.
The SQL schema still relies on service-level rules for some cross-table invariants;
model events do not protect arbitrary SQL/bulk writes to final grades.

## F. Test results

| Check | Result / actual current evidence |
| --- | --- |
| SQLite baseline | PASS — 67 tests / 874 assertions, 5.522 s |
| SQLite final | PASS — 71 tests / 946 assertions, 5.515 s |
| MySQL final | PASS — 19 tests / 139 assertions, 7.290 s |
| Concurrency | PASS — 7 scenarios included in MySQL suite, distinct connections and locking observed |
| PHP syntax | PASS — 100 PHP files checked; added MySQL test also checked after final edit |
| Laravel Pint | PASS — 99 active source files; historical QA archive excluded |
| Vite | PASS — 127 transformed modules, build 2.49 s; 10 manifest entries, all referenced files exist |
| Real HTTP fallback | PASS — 42 checks: landing, login, four-role dashboards/login/logout, 12 cross-role 403 responses, CSRF 419 rejection, redirect after logout |
| Browser/visual matrix | BLOCKED — MCPs unavailable, no screenshots/visual claims |
| Production security / pentest / sustained load | NOT RUN |

HTTP fixtures used a verified new empty temporary SQLite file. Setup ran in testing;
the localhost-only HTTP process used local environment so CSRF was actually enabled.
No port was published and no operational app was used. Original baseline assets
were reused for early feature checks; a fresh native Vite build then succeeded.
HTTP checks preceded the fresh build; current assets were subsequently verified
against the new manifest, but a browser asset/network/rendering pass was not run.

## G. UI/UX

Existing design retained. Added Owner totals with existing summary-card classes,
labelled their program/semester scope, and clarified final-grade summary text.
Feature tests rendered changed Blade templates; builds passed. Viewports 375×812,
390×844, 768×1024, 1366×768 and 1920×1080 were NOT visually tested in this session.
Keyboard focus, contrast, overflow, mobile sidebar, fonts and visual layout remain
unverified. Phase 3–6 screenshots/results are HISTORICAL PASS only.

## H. AI & academic policies

Implemented: stored risk snapshots, factors, recommendation and follow-up relations;
student ownership filtering; known demo-provenance exclusion; Owner latest-result
counts and explicit empty states. Columns include model version, actor, timestamp
and summary, but optional legacy provenance is not proof of validated AI processing.

Not implemented: risk engine, provider interface/pipeline, neutral processing jobs,
validated external-result ingestion, processing-status history, immutable model
registry/evaluation records, or human approval workflow. These remain PARTIAL /
MISSING. No external AI service was connected and no new score was generated.
`config/academic.php` contains pre-existing risk thresholds with no runtime consumer;
they were not activated or treated as an approved institutional algorithm.

NEEDS SPECIFICATION: indicators, data sufficiency, approved formula/model, weights,
thresholds/categories, evaluation/fairness criteria, provenance contract, retention,
human review/access responsibilities and recommendation/follow-up transitions.
Also needed: attendance recording/correction policy, reopening final grades,
historical-credit correction and account-administration responsibilities.

## I. Performance

No application timing before/after or load improvement is claimed. Prior WSL-versus-
Windows measurements remain historical. Existing eager loading and batched GPA
queries retained; no academic cache introduced. Owner totals add small aggregate
queries. Fresh build sizes include landing CSS 285.02 kB (gzip 44.38), dashboard CSS
272.69 kB (gzip 41.25), and shared chart JS 288.13 kB (gzip 95.02).
Large original identity PNGs remain a known historical optimization opportunity;
no new image derivatives, CDN/server changes or speculative caching were introduced.

## J. Shared hosting

See [concrete hosting requirements](shared-hosting-readiness.md). Hosting provider,
PHP extension set, actual database/version, public document root, TLS/proxy/session
configuration, cron/worker support, resource quotas and backup restoration are not
verified. No hosting compatibility or production readiness claim is made.

## K. Remaining work

Completed here: safe dependency recovery, fresh offline frontend installation/build,
three application fixes/completions, test-environment repair, new regression tests,
current SQLite/MySQL/concurrency/HTTP verification, source inventory and runbooks.

PARTIAL: browser/responsive QA; neutral AI contract and processing/audit pipeline;
attendance/follow-up mutation workflows; detailed lecturer navigation; final-grade
statistics/exportable reports; account lifecycle; comprehensive security/performance
assessment. Each requires its own acceptance criteria and verification.

Requires decisions: academic rules listed in H and report/export contents.
Requires separate authorization: operational backup/actual-data restore rehearsal,
operational migration/cutover/configuration changes, system software changes if
needed, and public deployment. Dependency download permission was supplied; no
network install was necessary because local dependencies/cache sufficed.

Disposable MySQL containers/networks and their temporary credential files were
removed by the harness. The final PHP/HTTP QA container is removed at session end;
no operational container is stopped or recreated. Dependencies/build and ignored
QA evidence remain in the workspace for review. No old archives were deleted.

## L. Final verdict

**PARTIALLY COMPLETE.** Core workflows have current automated QA evidence and the
identified summary/search defects are fixed. Full feature completion, browser QA,
AI governance, operational rollout and actual shared-hosting validation are not
complete. This application is not certified production-ready by this report.

## M. Pembaruan modul Admin Akademik

Laporan di atas merupakan snapshot audit sebelumnya. Implementasi dan bukti QA
Admin yang lebih baru tercatat dalam [admin-module-completion.md](admin-module-completion.md).
Laporan tersebut tidak menggantikan batasan browser QA dan rollout operasional.

## N. Pembaruan modul Owner/Kaprodi

Implementasi monitoring baca-saja, halaman detail/laporan, perbaikan filter IPS
dan warning CSS, serta hasil QA gabungan terbaru tersedia dalam
[owner-module-completion.md](owner-module-completion.md). Angka sebelumnya
tetap merupakan snapshot historis. Browser visual QA masih BLOCKED.

## O. Final system verification

Verifikasi lintas empat role, perbaikan UI/aksesibilitas/pagination dan kemampuan
public page, diagnosis browser Windows–WSL, hasil browser nyata, serta release
gates terbaru tercatat dalam [final-system-verification.md](final-system-verification.md).
Status BLOCKED browser pada bagian lama adalah snapshot historis; lihat hasil
akhir pada laporan terbaru. Kesiapan hosting/produksi tetap diperiksa terpisah.
