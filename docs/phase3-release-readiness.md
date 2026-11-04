# Phase 3 release review

Operational migration and workspace relocation remain unapproved. Phase 3 uses a
new MySQL 8.4.11 tmpfs server with separate browser and regression schemas. It does
not copy operational records or credentials. The Windows workspace remains the
working directory; Linux performance measurements use disposable source copies.

## Final verification after resume

The final source passed 67 SQLite in-memory tests / 874 assertions and 18 isolated
MySQL tests / 125 assertions, including seven concurrency scenarios. PHP syntax,
Pint test mode and Vite build passed; the existing Bootstrap @charset build warning
remains nonfatal. Browser retests verified unchecked checkbox recovery, reserved
email rejection at both profile entry points, protection of used credits, correct
cross-role login fallback and preservation of an authorized intended URL.

Reusing the old session cookie after logout returned 302 to login; the session
cookie changed after authentication. The QA data audit confirmed versions 1/2,
rejected/approved history, one/two-course snapshots, two final grades, IPS/IPK 3.60,
the original account roles and complete lineage for 131 live synthetic records.
The 25 QA student profiles include one deliberate demo sentinel; operational
queries correctly return only 24. This is not operational student data.

The read-only operational comparison matched counts/fingerprints on all 16 domain
and migration tables checked. The six applied migrations and one pending workflow
migration remain unchanged. Protected environment, Compose, lockfile and migration
file hashes also match the Phase 3 baseline. Detailed sanitized evidence and fresh
screenshots are retained under qa-artifacts/phase3-p3-mw28gmsp/resume-mw2ba7fh/.

Cleanup completed after ownership/mount verification: three QA containers, two
QA networks and six temporary credential/marker files were removed. Evidence
artifacts were retained. The operational containers remained healthy and the
MySQL volume stayed `uninalar_uninlar_mysql_data`.

## Verified fixes

1. Unchecked `aktif` was restored as checked after failed validation. The form now
   distinguishes a fresh form from a flashed request that omitted the checkbox.
   The failing SQLite assertion and QA browser reproduction were both retested.
2. Admin profile creation and student contact updates accepted the reserved demo
   email domain. Such a record immediately disappeared from operational queries.
   Both entry points reject that domain without modifying existing demo data.
3. Editing course credits after KRS use changed historical weighted GPA. The QA
   example changed 3.60 to 3.67 without changing grades. Course updates now lock
   the course/classes and inspect current detail rows before permitting a credit
   change. Used credits cannot be rewritten through ordinary CRUD. Corrections
   to historical credits require a separately designed, audited academic process.
4. Small sidebar role/navigation labels had low contrast on white. Their text
   color is now #64748b, measured at approximately 4.76:1. This is a targeted fix,
   not a claim of complete WCAG conformance or screen-reader certification.
5. A guest opening a lecturer URL before logging in as Admin was sent back to a
   forbidden page. Login now checks same-origin intended route metadata and level
   middleware before redirecting. Authorized intended URLs remain supported;
   invalid, external, guest-only and wrong-role targets fall back to the dashboard.

No grade thresholds, risk formula, AI integration, official schedule or repeat
policy was invented. Existing application rules remain the basis for tests.

## Realistic data and provenance

`phase3-fixtures.php` creates five synthetic accounts in a verified fresh QA schema.
Academic master records for the main journey are created through browser forms.
`phase3-populate.php` adds realistic Indonesian academic records for pagination,
eight weekly attendance records within the semester, and an explicit demo sentinel.
Names are fictional and email addresses use the reserved example.test domain.
The attendance timetable is a fixture convention, not an official university rule.

`phase3-data-audit.php` records row-level lineage in the **QA-only** table
`qa_fixture_provenance` with the run ID and `synthetic_phase3` source. These records
must never be imported into operations. Synthetic records intentionally exercise
operational queries only inside QA. The demo sentinel must remain excluded.

The primary journey uses two courses (3 and 2 credits), final A/B grades, and an
expected weighted IPS/IPK of 3.60. The rejected first submission keeps its one-course
snapshot; the approved second version contains two courses. Empty risk and
recommendation states are expected because no approved analysis was generated.

## Deployment gate: NEEDS APPROVAL

The pending migration remains:
`2026_10_29_000000_prepare_krs_submission_history_and_grade_finalization.php`.

Expected additions are the semester registration window, KRS decision metadata,
submission history with unique `(krs_id, versi)` and restrictive audit foreign keys,
and grade finalization metadata/index. Existing rows receive nullable metadata;
they are not automatically submitted, approved or finalized. A null KRS window
keeps registration closed. TIMESTAMP audit fields retain their 2038 limitation.

Before applying it:

1. Approve the maintenance window, exact target identity and protected backup path.
2. Pause writes and create a consistent operational backup, recording hashes,
   schema metadata, counts, grants and migration state without leaking secrets.
3. Restore that actual backup into an authorized isolated MySQL 8.4 server and
   validate data, constraints and the additive migration on the restored copy.
4. Check metadata-lock blockers and available resources. MySQL DDL may block
   traffic and is not an all-or-nothing Laravel transaction. Plan downtime.
5. After separate approval, deploy compatible code and the specific migration;
   inspect partial DDL on failure instead of blindly rerunning or resetting data.
6. Check schema readiness, role guards, UTC audit instants, Jakarta KRS windows,
   history versions, final grades and academic summaries before resuming writes.

Routine rollback should restore compatible code while retaining additive schema.
After workflow writes begin, do not run `down()` casually: it removes history and
finalization audit fields. Prefer a forward fix. A database restore requires
approval and reconciliation of all writes since backup. No operational backup or
restore was performed during Phase 3; the Phase 2 synthetic restore is not proof
that an operational backup can be restored.

## Remaining decisions and limits

* WSL/workspace migration is deferred. Do not change operational mounts to chase
  benchmark numbers. A separate proposal must preserve the exact MySQL volume.
* Academic correction/reopening procedures, official grade conversion thresholds,
  transfer rules and risk/AI provenance specifications need institutional decisions.
* The QA scenarios are not a load test or a complete accessibility/security audit.
  Very slow networks, offline fonts, assistive technologies and prolonged sessions
  need further testing. Do not label these untested cases PASS.
* Generated archives and QA artifacts are review material, not release assets.
  Do not commit temporary environments, session artifacts or credentials.
