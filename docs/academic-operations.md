# Academic operations and QA

## Database prerequisite

Base tables support class CRUD. Grade entry/finalization and KRS submission/review
require `2026_10_29_000000_prepare_krs_submission_history_and_grade_finalization`.
These source changes do not apply it. Operational migration requires separately
approved backup, restore verification and rollout.

Compose was verified as project `uninalar`, MySQL 8.4.11 database `uninalar`, volume
`uninalar_uninlar_mysql_data`. Host Artisan loads `.env` and can instead connect
to the separate host MariaDB. Verify both effective configuration and actual
server/database identity. Sandbox `permission denied` did not mean Docker was
stopped; an approved read-only command outside the sandbox reached the Engine.

## Rules implemented

- Admin Akademik manages classes. Course/semester/class-code combination is unique.
  Identity and lecturer are locked once KRS details reference a class. Deletion
  is refused with related KRS; capacity cannot fall below waiting/approved places.
  No class status column exists, so none is invented.
- Dosen enters/edits only approved KRS participants in assigned operational classes.
  Numbers are 0–100 with at most two decimals. Letter-to-points conversion uses
  `config/academic.php`. Numeric-to-letter bands are not defined and are not guessed.
- Finalization records UTC time and actor, rejects repeated finalization, and locks
  individual Eloquent updates/deletes. Model events cannot protect arbitrary SQL
  or bulk operations; no reopening route is added.
- IPS uses valid final grades weighted by SKS. IPK uses the latest finalized course
  attempt. Owner labels these all-history metrics; semester filtering affects KRS,
  analyses, recommendations and follow-ups. No owner-to-program mapping exists;
  the existing level-4 role monitors all programs.
- Shared operational filters check known demo code/email markers and JSON
  `ringkasan.sumber` values. Legacy records with no provenance cannot be classified
  conclusively. No records are renamed, deleted or assumed authentic to hide this.
- Risk/recommendation screens show stored records. No score algorithm or AI service
  is implemented. Official rules, missing-data handling, algorithm versioning and
  human review policy still require academic decisions.

Audit event columns retain the migration's TIMESTAMP design. Its 2038 range is
a long-term schema consideration; no automatic type conversion was performed.

## Isolated verification

`phpunit.xml` uses SQLite `:memory:` and array sessions/cache. `Tests\\TestCase`
rejects other databases before RefreshDatabase can run. Run PHPUnit with
`--do-not-cache-result`. MySQL tests need a separate verified disposable harness.

`tests/Support/browser-qa.php` is a CLI-only fixture builder. It accepts only a new
empty `uninalar-qa-<hex>.sqlite` file directly in the system temp directory, verifies
actual SQLite file identity before test migrations, and refuses other targets.
Required process-only settings: `APP_ENV=testing`, `DB_CONNECTION=sqlite`,
`DB_DATABASE` and `UNINALAR_QA_DATABASE` pointing to that file, empty `DB_URL`, and
`UNINALAR_QA_PASSWORD` with at least 24 characters. Fixtures are synthetic and
explicitly labelled QA Terisolasi.

A separate temporary localhost PHP server can use that same verified file with
`SESSION_DRIVER=database`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, and
`LOG_CHANNEL=stderr`. Browser login uses `qa_mahasiswa`, `qa_dosen`, `qa_admin`,
`qa_owner` on that test server only. Never submit QA forms to port 8080 or run the
fixture builder against Compose. No environment files need changing.
