# UniNalar Phase 6 — Linux native build and FPM readiness

Public-review note: user paths and local database UUIDs are redacted with
`<...>` placeholders. Substitute verified local values before executing procedures.

5 November 2026, Asia/Jakarta. Run `p6-mw31qkha`.
Evidence is under `qa-artifacts/p6-mw31qkha/`. Windows remains the main workspace.

## A. Executive summary — PARTIAL, no cutover

Native Linux installation/build and a real Nginx/PHP-FPM permission design passed
in an isolated Phase 6 copy. The existing Phase 5 staging and operational database
were preserved. Remote WSL editor/Codex/MCP use remains NOT TESTED; activation is
still NEEDS APPROVAL. No academic policy or operational migration was introduced.

## B. Staging verification — PASS

Before copying, 161 selected application/configuration/test/document/image files
in Windows matched Phase 5 staging exactly. Generated build/runtime/cache files were
explicitly excluded: Phase 5 intentionally has its own generated assets. Complete
Phase 5 before/after inventories match file paths, hashes, modes and types. Parent
directories also retain their original ownership/modes; no explicit POSIX ACL was
found on the inspected parent/staging paths.

The new disposable source is `/home/<WSL_USER>/projects/UniNalar-p6-mw31qkha`;
it was created only after verifying the path did not exist. A current allowlisted
archive excludes active environment files, SQLite data, runtime, Git metadata and
Windows node_modules. Its SHA256 is
`364e2e537116b8cfdd2fd7c125c660d6c081420a28b6d044c8b2734cc8141338`.
Existing tracked and untracked changes, old snapshots and QA evidence were retained.

## C. PHP-FPM permission diagnosis — PASS

The home directory and Phase 5 source are 0750, owned by 1000:1000. The source mount
appears as `/qa` inside the container; container workers traverse that mount's root,
not the host's `/home/...` ancestors. Broadening home permissions is unnecessary.
The operational pool is UID/GID 33:33. A UID 33/GID 33 probe on the Phase 6 copy
could neither read bootstrap/app.php nor write bootstrap/cache.

Temporary QA FPM instead runs workers as UID 33, GID 1000. The source is mounted
read-only, with separate writable mounts for storage and bootstrap/cache. The actual
HTTP worker probe returned source_readable=true, source_write_attempt=false,
storage_writable=true and bootstrap_cache_writable=true. It created a runtime file
as 33:1000 with mode 0660. A separate developer UID 1000 file was successfully
updated by UID 33/GID 1000, demonstrating shared runtime access.

QA Nginx uses an in-container group with GID 1000 to read static files from the same
read-only source. Only that disposable container's group database was changed;
no WSL/Windows group or operational image/configuration was modified. FPM and Nginx
configuration syntax checks passed. No chmod 777 or recursive chown was used.

## D. Recommended permission solution — VALIDATED IN QA ONLY

| Approach | Finding |
| --- | --- |
| Shared group, read-only code, separate writable runtime | Actual FPM/Nginx QA passed; preferred design to review |
| Targeted POSIX ACL | Could avoid changing primary groups; not modified or tested; extra audit/backup complexity |
| Align worker UID with developer UID | Simpler ownership but conflates worker/developer identity; not needed for this test |
| Loosen source/home to world access | Unnecessary and rejected |

For an approved permanent design, select a suitable group mapping explicitly rather
than blindly assigning the developer's GID everywhere. Limit worker access to mounted
code and runtime. Use setgid 2770 runtime directories, 0660 shared files and umask
0007 for both CLI and FPM. Initial QA CLI tests used the default umask; permissions
were normalized only in the newly created disposable runtime and subsequent CLI
commands used umask 0007. Do not apply those operations to existing staging implicitly.

The first SQLite invocation also tried to write PHPUnit's result cache into read-only
source. The final invocation used --do-not-cache-result and passed without that warning.
These were QA runner/configuration adjustments, not application logic bug fixes.

## E. Linux Node toolchain — PASS after scoped approval

Node already existed at `~/.nvm/versions/node/v24.21.0/bin/node`; the noninteractive
sh PATH had not initialized nvm. Node reports linux/x64, npm 11.19.0. The existing
.bashrc contains an nvm initialization reference. No Node/npm upgrade, global package
installation, profile edit or permanent PATH change was performed.

Vite 7.3.7's locally inspected engine range is ^20.19.0 || >=22.12.0, compatible
with the installed Node. The lockfile includes Linux esbuild/Rollup/Oxide/LightningCSS
variants. Windows node_modules was not copied. An offline npm ci failed ENOTCACHED;
the initial decision to remain offline was respected until explicit later approval.

After approval, cwd was verified as the Phase 6 copy, on WSL's Linux filesystem,
without .env or database.sqlite. npm ci --ignore-scripts --no-audit --no-fund used
registry.npmjs.org and the existing lockfile, installing 91 project packages in 14 s.
Only the QA project's node_modules was installed; ordinary user npm cache contents
may be populated, not global packages or system settings. Blank, separate npm user
and global configuration files lived only in QA; existing user configuration was
not loaded/edited. No lifecycle install scripts or npm suggested upgrade were run.

Lockfile SHA256 remained
`8094a59b5b09337c9d869fd9a15572a2a936b3936b3792f99f3347e17382d721`
before/after installation and build, matching the protected Windows lockfile.

## F. VS Code Remote WSL — NOT TESTED

Windows VS Code/Codex extensions are present. Remote WSL was not detected in the
standard Windows extension directory and no remote server session was verified.
The current Codex workspace is Windows. Do not infer remote readiness from this
session or install editor integration without approval.

Manual gate: in VS Code, inspect Remote WSL availability; after approval if installation
is required, open the chosen Linux QA folder using WSL: Open Folder in WSL. Confirm
the status bar says WSL: Ubuntu and integrated-terminal pwd resolves to /home/...,
not /mnt/c. Run node --version, npm --version, git --version and docker version in
that terminal. Recheck all user source changes before considering a canonical folder.

## G. Codex / MCP — PASS CURRENT SESSION, remote NOT TESTED

Playwright MCP and Chrome DevTools MCP both actually reached QA on localhost:8096
from this Windows session. Git 2.53.0 and Docker 29.6.2 work inside WSL. PHP 8.2.34
and Composer 2.10.3 are available through the existing app image; no native PHP was
installed. Native PHP remains optional for the Docker-based development approach.

In a future remote Codex session, verify its cwd/tool roots, credential/config paths,
both MCP connections and artifact destination independently. Open only the isolated
QA origin for mutative tests. A remote browser may have a different meaning of
localhost; verify the actual page/network before logging in. No remote Codex settings,
MCP settings, browser dependencies or editor extensions were changed here.

## H. Linux native build — PASS

Both Node and esbuild binaries begin with ELF magic 7f454c46, not Windows PE headers.
Vite transformed 127 modules. The final npm run build returned exit 0 in 2.50 s;
the first successful build reported 3.17 s. The existing Bootstrap @charset warnings
remain nonfatal. The final command redirected stderr inside Linux before reporting
its actual exit code, avoiding PowerShell's misleading NativeCommandError formatting
of ordinary build warnings. Evidence: native-vite-final.txt.

The generated landing-BrFvBaWJ.css and JS were actually loaded through QA Nginx/FPM;
login loaded login-aG1l97Gq.css and its JS. This is a native Linux build result, not
a Windows executable or a copied Windows dependency tree. Active Windows assets
were not replaced. QA build roots can yield different Tailwind candidate sets; no
claim of bit-identical CSS to an earlier Windows build is made.

## I. Automated tests — PASS

Final SQLite in-memory run: 67 tests, 874 assertions, 6.744 s, no result-cache warning.
MySQL integration: 18 tests, 125 assertions, 8.915 s, seven concurrency scenarios.
Separate worker connections observed FOR UPDATE; the test's SHOW STATUS deadlock
field was unavailable/null. An enabled InnoDB lock_deadlocks counter read returned 0.
This is not a load-test guarantee. PHP syntax/Pint checks passed for the QA HTTP
helper; no application source was edited to make tests pass.

The MySQL server is a unique 8.4.11 tmpfs instance, UUID
`<PHASE6_QA_DB_UUID>`, with no persistent volume/published DB port,
only an internal QA database network. Two schemas are qa_browser_p6_mw31qkha and
qa_tests_p6_mw31qkha. Actual container/network metadata was checked before application
schema writes. Guarded bootstraps compare manifest, hostname, database/user/host,
actual UUID/version and a server-side marker before migrations/fixtures/tests.

Only these schemas received migrations. The existing Phase 5 synthetic fixture
helper was reused with the new Phase 6 run ID; its source label remains
synthetic_phase5, explicitly denoting helper provenance. The audit adds lineage for
the UI-created history and grade. No such fixture was loaded into operations.

## J. Browser QA — PASS for exercised scope

Playwright checked desktop 1366 × 768 landing/login, all four navbar hashes and one
active link after scroll settled. No global horizontal overflow, JavaScript pageerror
or failed request was found. Landing and login screenshots were opened and visually
inspected. This is not a new exhaustive responsive/accessibility matrix.

Through actual FPM requests, all four QA roles logged in/out, brand links returned
to their own dashboards and 12 cross-role dashboard requests returned 403. The
student submitted KRS; Admin approved; assigned lecturer entered 90/A and finalized;
the edit form disappeared. Owner's database-derived IPS/IPK was 4.00 for the single
graded profile. Missing risk/recommendation records stayed empty; no score was invented.

DevTools observed all nine landing requests and seven login requests returning 200,
including the newly generated native CSS/JS, images and fonts; no console error or
warning was found. Operational login/forms were never used. Final screenshots show
only synthetic QA identities. This does not claim the full Phase 3 CRUD matrix was
repeated or that every browser workload is certified.

## K. Performance — measured QA only

Three CLI samples record Artisan, autoload, bootstrap and console Blade times in
performance-cli.json. Three warm browser samples after a warmup record TTFB/LCP/CLS
in performance-browser.json; medians and ranges are in metrics.json. Browser TTFB
median is 19.6 ms [18.7–25.1], LCP 144 ms [144–148], CLS 0 in those three samples.
No throttling, OS cold-cache reset or load test was performed. The QA isolation
gateway itself verifies MySQL identity before dynamic requests.

CLI medians [min–max]: Artisan wall 299.5 [295.3–318.0] ms, autoload 17.46
[17.26–17.60] ms, bootstrap 75.47 [74.44–75.48] ms and console Blade 8.61
[7.57–9.33] ms. Wall time includes Docker exec overhead.

Phase 5's Windows Artisan median 7.372 s and WSL 0.309 s remain historical context,
not a fresh paired comparison. Phase 6 validates actual Nginx/FPM on the QA copy;
it does not claim an operational performance change or equivalent gains everywhere.

## L. Changes and rollback scope

Added only this runbook and the run's QA artifacts: configs, HTTP probe, setup script,
logs, inventories, metrics, manifests, screenshots and source archive. Linux npm
dependencies/build outputs and disposable runtime permissions changed only in the
new Phase 6 QA copy/runtime. Existing application code, environment, lockfiles,
migrations, Compose and Phase 5 staging were not changed. No Git commit/reset/clean.

Undoing QA means stopping/removing only run-labelled containers/networks and deleting
the generated temporary credentials. Existing snapshots and approved QA dependencies
are retained. No global install/profile modification needs system rollback. Permanent
permission, editor or operational activation still requires separate approval.

## M. Operational database safety — PASS

The actual operational database remains uninalar, UUID
<OPERATIONAL_DB_UUID>, volume uninalar_uninlar_mysql_data. Read-only
before/after transactions matched counts and fingerprints for all 23 tables. All 14
protected file hashes matched, including .env/.env.docker, lockfiles, SQLite and
migrations. The workflow migration remains pending and operational readiness false.
No operational migration, seeder, backup, restore, connection change or reset occurred.

Final cleanup removed exactly three labelled QA containers, two owned QA networks
and three temporary credential files. No persistent QA volume existed. Native
dependencies, source/build copies, runtime evidence and user snapshots were retained.
Both browser MCPs returned to the public operational landing without login. The four
operational services remained up; app/db/web were healthy, /up returned HTTP 200
(one final sample 2.564 s), and the MySQL mount was still uninalar_uninlar_mysql_data.

## N. Cutover readiness gate

| Gate | Status |
| --- | --- |
| Current selected source matches Phase 5 | PASS; recheck at activation |
| User snapshots and changes preserved | PASS |
| Least-privilege FPM/Nginx design | PASS in disposable QA; permanent setup NEEDS APPROVAL |
| Native Linux Node/npm/Vite | PASS with existing runtime and approved QA install |
| Docker integration / isolated tests / browser | PASS for recorded QA scope |
| VS Code Remote WSL | NOT TESTED |
| Remote Codex/MCP and remote artifact paths | NOT TESTED |
| Operational migration/backup or cutover | NOT AUTHORIZED |

Do not activate while the remote-session gates or permanent permission plan remain
unresolved. Keep Windows as the authoritative workspace and rollback source. Follow
the Phase 5 cutover runbook: fresh snapshot/diff, protected environment provisioning,
exact Compose identity/volume verification and only approved app/web switching.

## O. Actions requiring approval

Remote WSL installation/settings if needed; permanent worker/group/mount/permission
changes; making WSL canonical; operational Compose/environment/app-web switching;
operational backup/restore/migration; deleting user snapshots; public deployment.
The scoped npm approval did not authorize any of these actions.

## P. Recommendation

The native build and FPM permission blockers are technically resolved in QA. Next,
verify an actual Remote WSL editor/Codex/MCP session and review a concrete permanent
permission/cutover diff. Do not combine that review with operational migration or
invent official academic correction/risk policies.
