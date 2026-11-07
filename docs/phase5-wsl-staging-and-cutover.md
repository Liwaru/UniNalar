# UniNalar Phase 5 — WSL staging and approval gates

Public-review note: user paths and local database UUIDs are redacted with
`<...>` placeholders. Substitute verified local values before executing procedures.

Date: 5 November 2026, Asia/Jakarta. Run: `p5-mw30lp6q`.
Evidence: `qa-artifacts/p5-mw30lp6q/`. This is preparation, not cutover.

## A. Executive summary — PARTIAL / NEEDS APPROVAL

A fresh, secret-free WSL ext4 staging snapshot passed source comparison, SQLite,
MySQL integration/concurrency and four-role browser journeys. Windows remains the
main workspace and operational services remain unchanged. Native Linux Node tooling,
Remote WSL/editor setup and compatibility with the existing UID 33 FPM workers are
not yet established. They are explicit activation gates, not implied approvals.

No academic policy, production optimization, operational migration or database
backup was implemented. Grade reopening, historical-credit corrections and risk
scoring remain separate institutional decisions.

## B. Windows workspace inventory — PASS

Main workspace: `C:\Users\<WINDOWS_USER>\<WORKSPACE>\UniNalar`.
Git base is recorded in `revision.txt`. The baseline includes existing untracked
Phase 4 documentation, profiler and evidence; none was discarded or committed.
`git-before.txt` lists tracked changes/untracked files using `-uall`.

The initial filesystem manifest includes 14,351 files / 504,401,188 bytes, zero
enumeration errors and zero symlinks. It includes ignored files, dependency trees,
hidden files, Git metadata, existing QA artifacts and documentation. Only the
currently growing Phase 5 evidence directory is omitted from recursive inventory.
Secrets are fingerprinted, never printed or included in the source archive.

Text-source metadata found 252 files containing LF, zero CRLF/mixed-source files
under the inventory's source filter. `git-line-endings.txt` and `git-file-modes.txt`
record tracked-file line endings/index modes separately. Git core.autocrlf is true;
no setting or line ending was changed. Commands execute PHP scripts explicitly,
without relying on a transferred Windows executable bit.

Important retained material includes Git history/uncommitted work, environment
files, lockfiles, SQLite file, migrations, Docker definitions, source, original
images, docs and all earlier QA artifacts. Inventory is not a backup of secrets or
an operational database export. Runtime storage may legitimately gain session/view
files during public requests; it is not the authoritative source snapshot.

## C. WSL inventory — PASS

Both previous directories were read without overwriting them:

* `/home/<WSL_USER>/projects/UniNalar`: 13,576 entries, zero inventory errors.
* `/home/<WSL_USER>/projects/UniNalar-p4-mw2z9jpd`: 8,770 entries, zero errors.

Complete path/hash/mtime/mode/type differences are retained, not just Git differences.
The old copy has 930 total differences and 84 core-source differences: 29 changed
files and 55 Windows-only files. These include workflow controllers/services/models,
views and tests; it must not overwrite the newer Windows source.

The Phase 4 subset has 5,644 total differences and nine core Windows-only files
(Docker definitions, four documents, package manifests and Vite config); no modified
core files were found at the initial comparison. Missing tooling/artifact/Git/runtime
paths are included in total counts, so those totals do not mean thousands of bugs.
Neither timestamps nor directory names alone were used to decide which copy to keep.

## D. Source consistency — PASS for staged snapshot

A new allowlisted archive contains current application/config/routes/views/tests,
vendor, migrations/factories/seeders, documentation, Docker definitions, package and
Composer lockfiles, Vite config and hidden source-control/editor convention files.
It includes the new QA fixture helper even though that helper is untracked.

The archive excludes active `.env*`, SQLite data, runtime storage, Git metadata,
node_modules and earlier QA artifacts. Those remain preserved and inventoried in
Windows; exclusions are intentional, not lost files. Existing vendor is reused, not
installed. The archive entries were checked for environment/database/Git paths.
Archive SHA256: `28a0dcde294743e61139876b8b94af7cc87a301b60ac505d0ab8f4626d4efd04`.

`source-consistency.json` verifies all 8,757 archived regular files against their
Windows counterparts with zero differences. Raw-byte equality also preserves line
endings. Staging has no symlinks or world-writable files. After this snapshot check,
QA manifests/runtime files and fresh generated build outputs were intentionally added
only to staging; mutable outputs are distinguished from immutable source.

## E. Editor / WSL / Docker compatibility — PARTIAL

Ubuntu WSL2 runs kernel 6.18.33.2 with `/home` on ext4. UID/GID is 1000:1000 and
Docker Engine 29.6.2 is reachable from the WSL Docker CLI. About 954 GiB was free
at the initial snapshot. No WSL/Windows setting or system package was changed.

Native WSL PHP and Node were not found. Composer/npm paths resolve to Windows
wrappers, not a validated native Linux toolchain. Windows has Node 24.14.1 and npm
11.11.0. The existing app image provides PHP 8.2.34, SQLite/MySQL extensions and
Composer 2.10.3; Composer's version command was actually executed. No install,
update, package download or Composer setup script was run.

Windows VS Code's launcher is visible in WSL. Remote WSL was not found in the
standard Windows extension inventory and a WSL server was not confirmed; full
editor compatibility is NOT TESTED. Codex and both browser MCPs work in this Windows
session, but a future Remote WSL session must verify its own tools/config paths.
No configuration/credential files were copied to fake that integration.

## F. Staging setup — PASS for isolated QA, not operational FPM

New source: `/home/<WSL_USER>/projects/UniNalar-p5-mw30lp6q`.
It was created only after proving the path did not exist. Extraction used the WSL
developer account, umask 027 and no-same-owner. Source, cache and storage are owned
by UID 1000 and directories use 0750; no world-writable files were detected.
QA PHP processes use UID/GID 1000:1000, so they do not create root-owned view caches.

**Activation gate:** existing operational FPM workers are UID/GID 33:33. A read-only
probe as UID 33 returned source-readable=false and storage-writable=false for the
new staging. The current operational FPM configuration therefore cannot simply be
pointed at this private staging. A reviewed user/group/ACL or container-user design
is required. Do not use chmod 777 or claim the FPM cutover has passed.

The temporary QA setup used uniquely labelled app/control/database containers:

* WSL PHP built-in HTTP server, loopback 8094, WSL bind mount.
* Windows PHP control, loopback 8095, selected source mounts read-only; its writable
  runtime directories were container-local and owned by UID 1000.
* MySQL 8.4.11, no published port, tmpfs at `/var/lib/mysql`, no persistent volume,
  attached only to the run's internal QA database network.

QA app containers joined only the run's QA networks, never operational Compose.
No extra server on ports 8080/8081/3308 or second process on the operational volume
was started. The scripts use existing images with pull=never. Temporary generated
credentials live outside the repository and are removed during cleanup.

## G. Automated QA — PASS; native Linux frontend tooling BLOCKED

MySQL identity was verified before application schema writes:

* Server UUID `<PHASE5_QA_DB_UUID>`, version 8.4.11.
* Browser database `qa_browser_p5_mw30lp6q`.
* Regression database `qa_tests_p5_mw30lp6q`.
* Operational UUID is different: `<OPERATIONAL_DB_UUID>`.

Each mutative QA bootstrap verifies testing environment, `/qa` base path, absence
of operational `.env`, manifest/container hostname, database name/user/host, actual
server UUID/version and server-side isolation marker. Actual Docker metadata first
proved the internal network/tmpfs/no-volume arrangement. These manifests are evidence,
not a substitute for checking actual mounts before future reuse.

Only the QA databases received all seven migrations. The browser schema received
five fictional accounts, two fictional student profiles, three programs and academic
records from `tests/Support/phase5-fixtures.php`. All live journey records have explicit
`synthetic_phase5` lineage in the QA-only provenance table: 18 rows after UI activity.
These fixtures must never be imported into operations.

* SQLite in-memory: **67 tests / 874 assertions PASS**, 5.723 s inside PHPUnit.
* MySQL 8.4.11: **18 tests / 125 assertions PASS**, 6.996 s inside PHPUnit.
* Seven concurrency cases passed using distinct connections and observed FOR UPDATE:
  duplicate submission, last seat, competing decisions, grade creation, finalize/edit,
  stale edits and related class deletion. The tests' SHOW STATUS deadlock value was
  unavailable/null; a separate root read of enabled InnoDB lock_deadlocks reported 0.
  That is not proof against deadlocks under every workload.
* PHP syntax and Pint test mode passed for the added fixture helper; diff checks pass.

Native Linux npm/Vite is BLOCKED by the missing Linux Node toolchain. Without any
installation, a separate Windows QA build workspace reused copied existing
node_modules and the complete secret-free source snapshot. Vite 7.3.7 built 127
modules with **exit code 0**, reporting 14.01 s. The known Bootstrap @charset warning
remains nonfatal. This is a Windows-host build, not a native WSL build claim.

Early sandbox spawn EPERM and PowerShell stderr/warning handling were QA harness
issues; native command redirection established the final exit code. Fresh generated
assets were then copied only to WSL QA, preserving its old assets/manifest evidence,
and actually loaded in both browser MCPs. The active Windows build was untouched.
There is no claim of bit-identical generated CSS across different Tailwind scan roots.

## H. Browser QA — PASS for exercised scope

Playwright MCP actually logged in and out all four QA roles. Each dashboard loaded,
each brand link returned to that role's dashboard, all 12 other-role dashboard
requests returned 403, and protected requests after logout returned 302.

Eight student pages returned 200 with correct page titles and no horizontal overflow:
dashboard, KRS, grades, attendance, risk, recommendations, follow-up and profile.
Six Admin master lists and their six create pages returned 200: programs, semesters,
courses, classes, lecturers and students. These browser checks do not claim full
CRUD mutation coverage; automated regression and earlier Phase 3 coverage are separate.

The UI journey submitted the preselected synthetic KRS, approved it as Admin,
entered 90/A as the assigned lecturer and finalized it. The final edit form disappeared;
student grades came from QA MySQL and Owner summary showed IPS/IPK 4.00 for the one
graded student. The second profile has null IPS/IPK and no fabricated risk results.
An audit confirmed version 1 approved, its one-course/3-SKS snapshot, the final grade,
and complete row-level provenance. The grade letter was chosen explicitly as QA input;
no numeric-to-letter policy was invented.

With newly built assets, student KRS at 1366, 820 and 390 px had no document-level
horizontal overflow and one active menu. The narrow table uses intentional internal
scrolling. Desktop and mobile screenshots were actually opened and visually inspected.
These samples are not the prior 96-combination Phase 3 matrix rerun.

Landing tracking cleanup preserved `keep=1` and the hash while removing utm_source.
An initial 700-ms probe caught a smooth scroll in progress; after settling, Tentang
was the sole active link at 89.5 px. A subsequent geometry-based wait verified Fitur,
Cara Kerja and Tentang near 90 px, and Beranda at the top. No speculative JS/CSS fix
was made for a transient measurement.

Chrome DevTools observed nine requests returning 200 and no console errors/warnings
on the original QA assets and again on the newly built CSS. Playwright recorded no
pageerror/failed requests in the role/new-build and performance runs. No operational
login or form submission occurred. Screenshots contain synthetic QA identities only.

## I. Performance — PASS within local experiment scope

Three alternating Windows/WSL samples used the same PHP image, immutable source,
dependencies and QA settings. CLI profiling renders with SQLite memory configuration
in both controls; public HTTP uses the same shared isolated MySQL and guarded router.
The matched browser series used the original identical build assets, before fresh QA
assets were installed. No service/cache/environment optimization was applied to operations.

| Metric | Windows median [min–max] | WSL median [min–max] |
| --- | ---: | ---: |
| PHP exec wall | 194.7 [182.4–319.0] ms | 179.1 [172.7–189.6] ms |
| Artisan wall | 7,371.7 [6,613.1–8,137.1] ms | 308.6 [300.7–326.9] ms |
| Composer autoload | 714.9 [697.6–735.2] ms | 20.7 [17.7–23.8] ms |
| Laravel bootstrap | 3,861.8 [3,847.8–4,106.2] ms | 78.9 [75.7–91.4] ms |
| Console landing Blade | 750.2 [639.8–783.9] ms | 8.5 [7.9–8.9] ms |
| Identical 3-test/30-assertion subset wall | 21,045.6 [19,073.2–21,131.3] ms | 777.1 [585.3–779.7] ms |
| Landing TTFB | 2,512.7 [2,396.4–2,529.0] ms | 17.9 [17.9–23.4] ms |
| Landing LCP | 2,688 [2,560–2,704] ms | 128 [124–160] ms |
| Landing CLS | 0.01219 [same] | 0.01219 [same] |

Wall measurements include Docker exec, not just Laravel. The repeated subset is
LoginIntendedRoleTest, not the entire test suite. All six subset runs passed.
Browser measurement used a warmup per origin and three alternating warm rounds at
1366 × 768. Built JS/CSS requests all returned 200; individual asset durations are
retained in browser-performance.json. CLI/build work may share machine resources;
no claim of an otherwise unloaded machine or pure OS cold start is made.

Phase 4 Artisan medians were 7,124/308 ms and QA TTFB 2,050/14 ms. Phase 5 reproduces
the broad filesystem effect, not a new improvement to the active application. No
cache flush, Docker restart of operations or OS cold-cache reset was used. This is
not a production load test or proof every authenticated page gains the same factor.

## J. Cutover plan — NOT EXECUTED / NEEDS APPROVAL

1. Agree on one canonical WSL workspace and an explicit editing freeze. Preserve the
   Windows original and both old WSL copies. Never overlay the old WSL source on
   Windows. Review manifests, tracked/untracked work and changes after this snapshot.
2. Create an approved source snapshot of the then-latest state, including a separate
   protected backup of Git metadata and required QA artifacts. Exclude operational
   secrets/data from ordinary archives; inventory symlinks and executable modes.
3. Approve/install Remote WSL if needed, then open the chosen directory from a WSL
   terminal using `code .`. Confirm editor/terminal cwd is `/home/...`, not `/mnt/c`.
   Verify Codex tool roots and both MCP connections in that actual remote session.
4. Approve a native Linux Node/npm solution or a reviewed, pinned build container.
   Use the existing PHP/Composer image without installing PHP on WSL if desired.
   Never run Composer `setup`: it includes migrations. Review scripts before any
   Composer/npm dependency operation; no install was done in this phase.
5. Approve a specific FPM ownership/group/ACL or runtime-user configuration and test
   it on staging. Ensure workers can read code and write only cache/storage. Keep
   secret directories private and avoid world-writable permissions. No host WSL or
   operational Compose permission setting is implicitly approved by this report.
6. Provision protected operational environment files separately after approval.
   Validate effective values without printing keys/passwords. QA credentials/manifests
   must never be used operationally. Keep QA session cookies/context distinct to avoid
   localhost cookie collisions across ports; never test writes against operations.
7. Reverify Docker project labels, effective Compose configuration and actual volume
   mount. Project must remain `uninalar`, database volume exactly
   `uninalar_uninlar_mysql_data`; folder naming is not proof. Do not start another
   MySQL process on that volume or recreate an empty database as a substitute.
8. Inventory port listeners and pause only the identified old app/web services if the
   approved switch requires it. Preserve DB/volume and other projects. No down -v,
   prune, reset, broad stop or migration. Review the exact prospective Compose action
   before executing it; do not let dependency recreation happen implicitly.
9. Start the approved app/web target once; verify mounts, worker permissions, health,
   actual PDO identity, migration state and database fingerprints before any writes.
   Retest public/login, four-role authorization/navigation, assets and tooling. Resume
   editing only when the selected workspace and recovery path are unambiguous.

Future operational code/schema rollout needs its own maintenance/backup approval;
development relocation is not migration permission. The current QA Compose files
were only copied as source and were never executed from staging.

## K. Rollback plan — PREPARED, not exercised operationally

If activation fails, pause the approved new app/web instance, preserve logs and
source changes, then restore the original Windows app/web mounts/configuration using
the recorded project identity and the same database volume. Avoid keeping two writers
or competing ports. Do not overwrite newer WSL edits with the Windows snapshot;
reconcile them through a manifest/diff first. Validate database identity, pending
migration state, worker access, /up, login and read-only smoke tests before resuming.

No database restore is normally needed for a source-only switch. If data changed,
stop and investigate; restore requires separate approval and write reconciliation.
Never run the pending migration's down() to undo a workspace move. Retain Windows
as a fallback until an agreed stabilization period, not merely the first green test.

## L. Database safety — PASS for read-only verification

Operational database remains `uninalar`, MySQL 8.4.11, UUID
`<OPERATIONAL_DB_UUID>`, Compose volume
`uninalar_uninlar_mysql_data`. The readiness audit uses a READ ONLY transaction and
compares 23 table counts/fingerprints. Six migrations remain applied and the workflow
migration remains pending; KRS/finalization readiness is expected false operationally.
Four users and 40 existing demo profiles are not deleted/relabelled.

No operational dump, restore, migration, rollback, seeder, reset or data mutation was
run. Phase 4's recovery runbook still applies: obtain backup authorization, pause
writes/DDL appropriately, create a protected consistent backup, checksum/encrypt it,
and restore the actual backup into a separate MySQL 8.4 server before schema rollout.
The prior synthetic restore is not proof the real backup is recoverable. TIMESTAMP's
2038 limitation remains documented; schema types were not changed.

Final verification matched all 23 operational table counts/checksums and all 14
protected-file hashes. Cleanup removed only the three run-labelled QA containers,
two owned QA networks and three temporary credential files; no persistent QA volume
was created. The new WSL staging, build/source snapshots and earlier artifacts are
retained. The four operational services remained up, app/db/web healthy, and /up
returned 200 (one final sample 2.173 s). Both browser MCPs were returned to the public
operational landing without logging in.

## M. Files added

* `tests/Support/phase5-fixtures.php`: guarded, QA-only fictional fixture/provenance
  preparation; not registered in production routes or ordinary DatabaseSeeder.
* This runbook.
* Phase 5 evidence, manifests, inventories, scripts, source/build snapshots, logs,
  browser JSON and screenshots under the run directory. They are review artifacts,
  not release/dependency assets to blindly add to Git.

No application controller/model/service/Blade/CSS/JS, existing migration, environment,
operational Docker definition, lockfile or original asset was changed. Existing
Phase 4 untracked work is retained. No Git commit/reset/clean was performed.

## N. Remaining risks

Native WSL Node/Vite, Remote WSL setup, remote Codex/MCP integration and FPM UID 33
compatibility are not ready. The verified QA server is a built-in PHP server, not
an operational FPM/Nginx cutover test. QA datasets are small; load, long-lived sessions,
large-data performance, prolonged WSL stability and full accessibility remain untested.
The Bootstrap build warning and oversized existing logo images remain known items.
Source and artifacts can change after this snapshot and must be rechecked at activation.

## O. Actions requiring separate approval

* Make a WSL directory canonical, stop/switch operational app/web or change mounts,
  Compose, runtime users, environment, ports or operational credentials.
* Install/configure editor integration, Linux Node or other system dependencies,
  fetch a new build image, or change permanent WSL/Windows settings.
* Provision/copy operational secrets, export/restore operational database backups,
  apply pending migration, delete old workspaces/artifacts or deploy publicly.

There is no pending request to approve all these actions as one bundle. The safe
next review is the concrete toolchain/permission/cutover plan; migration approval
and official academic policies must stay separate.

## P. Final recommendation

WSL staging is validated for the exercised Docker/SQLite/MySQL/browser paths and
shows substantial repeatable local filesystem benefits. Keep Windows active until
toolchain/editor/FPM gates are resolved and a specific cutover is approved. Do not
call this staging validation an activated development migration.
