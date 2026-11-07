# UniNalar Phase 4 — performance and recovery readiness

Public-review note: user paths and local database UUIDs are redacted with
`<...>` placeholders. Substitute verified local values before executing procedures.

Review date: 5 November 2026, Asia/Jakarta. Evidence run: `p4-mw2z9jpd`.
Evidence directory: `qa-artifacts/p4-mw2z9jpd/`.

## A. Executive summary — PARTIAL

The Windows bind mount is the dominant measured startup overhead. A real Ubuntu
WSL2 ext4 bind mount reproduced the low startup cost previously observed with a
container-local copy. This supports a separately approved development relocation;
it is not an operational relocation or a promise of equivalent production gains.
No application optimization, academic policy, operational cache change or migration
was applied. The existing application code did not require a speculative fix.

## B. Current environment — PASS

Git started clean at `fcb90b0`. Windows remains the main workspace. Docker Engine
29.6.2, Ubuntu WSL2 kernel 6.18.33.2 and Docker integration are available. Initial
sandbox WSL/Docker access failed; the same read-only checks succeeded with approved
access outside the sandbox. No system setting or execution policy was changed.
PowerShell file execution is disabled; inspected QA commands were executed inline.

The existing four Compose services were already running; no operational service
was restarted. Project identity is `uninalar`; the database is MySQL 8.4.11,
`uninalar`, UUID `<OPERATIONAL_DB_UUID>`, with volume
`uninalar_uninlar_mysql_data`. There are 23 InnoDB tables, 23 foreign keys and six
applied migrations. The workflow migration remains pending. Approximate user-schema
data plus index allocation from information_schema is 884,736 bytes; this is not
the size of a backup, the full MySQL volume or free-space verification.

## C. Root cause analysis — PASS within measured scope

Three disposable PHP 8.2.34 containers used the same existing image, QA environment,
source selection and dependencies. No operational `.env`, SQLite file or MySQL
connection was included. The Windows control mounted selected source paths read-only;
the other controls used container-local storage and a real Ubuntu ext4 bind mount.
All used `testing`, SQLite `:memory:`, array sessions/cache and CLI OPcache disabled.
No container joined the operational Compose network.

The final CLI series alternated Windows, WSL and container-local measurements for
three rounds after MySQL initialization. Wall times include Docker exec and process
startup. PHP-only exec measurements include CLI version output and provide an
approximate overhead baseline, not a directly subtractable pure Docker measurement.

| Median, n=3 | Windows bind | WSL ext4 bind | Container filesystem |
| --- | ---: | ---: | ---: |
| PHP exec wall | 176.6 ms | 202.3 ms | 172.2 ms |
| Artisan version wall | 7,123.7 ms | 307.8 ms | 395.9 ms |
| Composer autoload in process | 652.3 ms | 19.7 ms | 18.3 ms |
| Kernel bootstrap in process | 3,844.0 ms | 139.6 ms | 96.6 ms |
| Landing Blade render in console | 567.9 ms | 10.4 ms | 8.8 ms |
| Read 150 already-included files | 1,563.7 ms | 3.5 ms | 5.0 ms |

The profiler uses identical bootstrap callbacks in each copy. Configuration loading,
framework provider registration and provider boot all slow down with Windows file
access. The application's sole AppServiceProvider has empty register/boot methods;
no application startup query, directory scan or slow custom provider was found.
Composer already has optimized autoloading configured. Configuration cache was off
in all controls and in the operational app. No production cache was changed.

A fresh operational PDO connection measured 24.95 ms once; five subsequent SELECT 1
queries took 0.32–0.37 ms. These small queries do not establish performance of every
academic query, but they do not explain the multi-second bootstrap measurements.
There is no evidence attributing the issue to antivirus or synchronization software;
their settings were not changed. This was not syscall-level tracing.

## D. Windows versus Linux HTTP/browser comparison — PASS with limits

The controlled servers used the same PHP built-in server and application assets.
The first CLI/HTTP series is retained separately; MySQL initialization overlapped
part of that early work, including a WSL outlier. The final browser series used one
unmeasured warmup per origin and three alternating measured rounds, without a
concurrent CLI benchmark. Viewport: 1366 × 768, no artificial throttling.

| Browser median, n=3 | Windows QA :8097 | WSL QA :8098 | Linux QA :8096 | Existing Nginx/FPM :8080 |
| --- | ---: | ---: | ---: | ---: |
| TTFB | 2,050.3 ms | 13.5 ms | 13.2 ms | 2,155.2 ms |
| LCP | 2,192 ms | 144 ms | 124 ms | 2,328 ms |

Nginx/FPM is contextual, not a controlled web-server comparison. Timings vary:
the operational measured TTFB range was 2,036.7–5,299.6 ms. The prior Phase 3
operational LCP/TTFB medians were 5,415/5,043 ms; a lower median now is not evidence
of an operational optimization because no such change occurred.

Chrome DevTools separately recorded WSL LCP 427 ms, TTFB 27 ms and CLS 0.01;
the operational trace recorded LCP 5,563 ms, TTFB 5,203 ms and CLS 0.01. These are
single trace samples, not the medians above. The operational trace attributed most
LCP delay to document response. No true OS/filesystem cold-cache test, sustained
load test, field telemetry, large operational-data test or Nginx/FPM-on-WSL test was
performed. First observed requests are not claimed as cold starts.

## E. Proposed WSL development setup — NEEDS APPROVAL

The experimental source is
`/home/<WSL_USER>/projects/UniNalar-p4-mw2z9jpd`. The old WSL copy at
`/home/<WSL_USER>/projects/UniNalar` and the original Windows workspace are
preserved. The experiment has no operational environment or data and is not the
active development workspace. Windows VS Code's launcher exists inside WSL, but
Remote WSL/server installation was not verified in common extension locations.
No extensions or native PHP/Node/dependencies were installed.

Before a real switch, obtain approval for the destination, editor integration,
permission setup, credential provisioning and service cutover. Copy source through
an explicit allowlist; preserve hidden source files, lockfiles and user changes.
Provision operational secrets separately, never inside archives or QA artifacts.
Use a reviewed UID/GID/group approach: this root-run QA container created compiled
view storage owned by root, although developer source is writable. Do not solve
that with world-writable permissions.

At cutover, inspect actual Docker project labels and MySQL mounts again. Reusing
the folder name is insufficient. Prove the effective Compose project is `uninalar`
and the database mount is exactly `uninalar_uninlar_mysql_data`; do not start a
second database instance against that volume. Avoid running old/new services on
the same ports concurrently. Any Compose, environment or port adjustment requires
separate approval. Keep the Windows copy for recovery and define one editing
source of truth; do not maintain diverging writable copies indefinitely.

## F. Safe optimizations implemented — no operational change

Only a guarded QA profiler and supporting evidence/runbook were added. Application
routes, controllers, services, assets and academic calculations were left intact.
Measured filesystem gains are experiments, not deployed performance improvements.
There was no arbitrary config/route/view cache warmup or dependency optimization.

DevTools found oversized existing PNGs: Icon 1,140,327 bytes at 1254 × 1254, and
Sidebar 476,686 bytes at 2172 × 724, displayed much smaller. Estimated image
transfer savings were about 1.6 MB, with no estimated LCP savings in that trace.
Future work can introduce reviewed, appropriately sized derivatives while keeping
the original identity assets. No image, markup, font or design was changed here.
HTTP compression/static cache headers need a separately reviewed server change.

## G. Database backup readiness — PARTIAL

A new MySQL 8.4.11 server, UUID `<PHASE4_QA_DB_UUID>`, ran with
network `none`, no published port and tmpfs at `/var/lib/mysql`. It had no persistent
volume. The test checked the server identity, absence of `uninalar` and unused QA
schema names before writes. Only synthetic parent/child/provenance tables existed
in `qa_p4_source` and `qa_p4_restore`.

The rehearsal used mysqldump single-transaction/quick, routines/triggers/events,
hex-blob, no-tablespaces and set-gtid-purged=OFF; no such command targeted operations.
SHA256 verified the original dump before import. Data-only dumps, column metadata,
index metadata and referential constraint metadata matched after restore. A parent
delete was correctly rejected with error 1451 and child data remained 2 rows,
sum 7.60. No nonempty routine/event/trigger or production grant recovery was tested.

Harness issues were diagnosed rather than hidden: an early connection reached
the MySQL initialization server before final readiness; `cmp` was absent; and raw
dump hashes differed because MySQL rendered explicit column character sets after
restore. Final verification compared data and semantic schema metadata and passed.
Shell input was normalized to LF after Windows pipeline CRLF produced an extra
trailing command. These were QA harness issues, not application bug fixes.

This proves the small synthetic mechanism, not restorability of the actual backup.
No operational backup was exported or restored. A real rehearsal remains mandatory.

### Operational procedure to approve before execution

1. Define recovery point/time objectives, maintenance owner, write pause, backup
   retention, encryption keys and an access-controlled location outside the repo,
   public document root, QA artifacts and automatic shared/sync folders. No backup
   directory or operational export was created in this phase.
2. Reverify host/port/database/server UUID and the actual volume. Inventory engines,
   schema, pending migrations, triggers/routines/events, grants and available disk.
3. Freeze application/background writes and prohibit concurrent DDL. All inspected
   user tables are InnoDB; single-transaction protects row consistency, not concurrent
   schema changes or changes in nontransactional engines. Review backup privileges.
4. Use a short-lived, protected client option file, with Unix 0600 or Windows ACL
   equivalent. Put `--defaults-extra-file` first. Never put passwords in CLI arguments,
   source, logs or dump names. Avoid shell redirection of SQL through PowerShell
   encoding; use mysqldump `--result-file` or container-native binary-safe output.
5. Produce schema/data plus required metadata, record completion status and SHA256,
   encrypt and store separately, then verify the stored checksum. A zero exit status
   or checksum alone does not prove recoverability.
6. After separate authorization, restore that actual backup into a new MySQL 8.4
   server without operational mounts/networks. Verify constraints, types, indexes,
   record counts/fingerprints, UTF-8, timestamps/timezones, migration state and app
   queries. Rehearse the pending migration on the restored copy and measure recovery
   duration. Keep credentials, encryption keys and recovery instructions separate.

## H. Migration safety plan — NEEDS APPROVAL

The existing code correctly reports KRS and grade readiness false. Read-only inspection
found the expected pending differences: two semester window columns, three KRS
decision metadata columns, the whole submission-history table with its unique
version/queue index and restrictive audit foreign keys, and two grade finalization
columns plus their index/foreign key. The migration was not edited or executed.

After actual backup restore verification and approval: pause writes; record the exact
code release and schema; deploy compatible schema-guarded code; apply only the reviewed
pending migration to the verified target; inspect actual schema/indexes/foreign keys;
perform role smoke tests; then resume writes. Do not run Composer `setup`: its scripts
include migrations. The current Dockerfile starts PHP-FPM and does not run migration
or seeding; MySQL's operational volume must never be replaced by an empty one.

MySQL DDL can commit per statement and hold metadata locks. If it fails, preserve
evidence and inspect partial DDL instead of blindly rerunning, resetting or assuming
transaction rollback. Prefer compatible-code rollback while retaining additive schema,
or a reviewed forward fix. Once history/finalization records exist, `down()` destroys
audit metadata. Restore requires separate approval and reconciliation of writes since
the backup. Nullable new fields do not invent approvals/final grades, and null KRS
windows remain closed. TIMESTAMP's 2038 range remains documented, not auto-converted.

## I. Automated tests — PASS for current isolated SQLite run

From the WSL source copy, PHP 8.2.34, SQLite in-memory:
`php vendor/bin/phpunit --colors=never` passed 67 tests / 874 assertions in 5.122 s.
The guard in Tests/TestCase verifies testing/SQLite/:memory: before schema writes.
The earlier `artisan test` invocation showed missing `/qa/.env` runner warnings in
this intentionally secret-free copy; direct PHPUnit passed without those warnings.
No operational environment file was copied to silence the runner.

PHP syntax and Pint test mode passed for the new profiler; git diff whitespace checks
passed. No frontend source changed, so Vite was not rebuilt. Phase 3 build PASS and
18 MySQL tests / 125 assertions, including seven concurrency scenarios, are historical
evidence, not Phase 4 reruns. No new MySQL application/concurrency claim is made.

## J. Playwright / Chrome DevTools — PASS for public smoke scope

Both MCPs actually ran. Landing pages on all three QA origins and operations returned
200. Playwright observed no pageerror, failed requests or failed assets in the first
three-load comparison. WSL navbar Fitur/Cara Kerja/Tentang targeted their correct
hashes, stopped around 90 px below the viewport top and had one active link each.
Beranda became the sole active item. WSL login rendered inputs and HTTP 200 without
horizontal overflow. The desktop screenshot was opened and visually inspected.

All three QA login pages returned 200 and the actual Owner route `/dashboard/owner`
redirected unauthenticated requests to login. A first guessed `/dashboard/kaprodi`
probe returned 404; routes/web.php confirmed it was not an application route and the
correct Owner route was retested. This was not reported as an application defect.

DevTools observed nine WSL document/asset/font requests returning 200 and no console
errors/warnings; the operational landing also had no console errors/warnings.
No operational login/form submission was performed. Four-role authenticated browser
journeys, CRUD, concurrency and responsive matrices were not rerun; Phase 3 evidence
remains distinct. This phase does not claim full WSL feature QA or mobile QA.

## K. Files changed

Added `tests/Support/phase4-profile.php`, this document, and the run's QA artifacts
(allowlisted source archive, safe metadata fingerprints, benchmark/HTTP/browser JSON,
synthetic dumps, scripts, test logs and desktop screenshot). Artifacts contain no
operational records or environment files. Existing tracked source was not changed.
Temporary QA credentials are outside the repository and are removed at cleanup.

## L. Operational integrity

Before/after audit used an explicitly READ ONLY MySQL transaction. SHA256 fingerprints
and counts matched for every one of the 23 tables, including migration state. The
four users and all 40 existing demo student profiles were preserved. Environment,
Compose, Dockerfile, lockfiles, SQLite data and migration hashes are checked separately.
No operational export, migration, seeder, credential change, reset, volume change,
workspace relocation, service cutover or public deployment occurred.

Final cleanup removed exactly four run-labelled QA containers, including the MySQL
tmpfs server, and two temporary credential files. No QA network was created; no
persistent QA volume existed. The experimental WSL source and evidence remain for
review. All four operational containers stayed running; app/db/web were healthy and
`/up` returned HTTP 200 (one final sample: 3.304 s). All 14 protected file hashes matched.

## M. Separate academic decisions — NEEDS OFFICIAL POLICY

| Decision | Current safe behavior | Required institutional decision |
| --- | --- | --- |
| Reopen/correct final grades | Final values remain locked | Who may request/authorize/reopen, evidence, audit retention and recomputation |
| Correct historical credits | Ordinary CRUD rejects SKS changes after use | Whether corrections are retroactive, record versioning, transcript/GPA reconciliation |
| Risk algorithm and AI | No invented score or recommendation replaces missing data | Indicators, validated formula/thresholds, data sufficiency, governance, explanation provenance |

These are product/academic governance decisions, not performance defects. No score
formula, opening authority, correction procedure or automatic academic outcome was
introduced to fill these gaps.

## N. Recommended next steps

First approve a WSL development-cutover plan covering editor support and ownership,
without combining it with the schema rollout. Then approve a protected operational
backup plus an actual isolated restore rehearsal. Only after reviewing those results
decide the operational migration window. Obtain academic policy independently before
implementing grade reopening, historical corrections or risk scoring.

## O. Final conclusion

WSL is a technically viable performance direction based on actual ext4/container
measurements and SQLite compatibility. Operational adoption and migration remain
NEEDS APPROVAL. The application remains on the original Windows workspace.
