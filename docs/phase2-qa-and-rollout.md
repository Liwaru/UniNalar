# Phase 2: isolated QA and rollout requirements

This phase does not authorize operational migrations or data changes.

## Changes

* Capacity checks use a locking read joining `krs_detail` and `krs`. Under MySQL
  REPEATABLE READ, a nonlocking status subquery can observe an older snapshot even
  after waiting for the class lock. The join reads the committed reservation.
* Grade forms submit a revision fingerprint. The service compares it against the
  current grade after acquiring locks. Missing/stale fingerprints cannot overwrite
  an existing grade. Final grades remain immutable. This is concurrency detection,
  not an authorization token; assigned-lecturer authorization is still required.
* Schema readiness reads column metadata directly instead of issuing an existence
  query first. Repeated guards and duplicate index queries are removed. There is no
  persistent schema cache; checks still see later schema changes.

## Isolated MySQL test harness

`phpunit.mysql.xml` is separate from the SQLite suite. It must run in a disposable
Linux PHP container whose base path starts with `/qa`, without an operational
`.env`, on an isolated MySQL 8.4 server. Never run it through the Compose app.

`tests/Support/IsolatedMysql.php` checks testing environment, hostname, explicit
connection configuration, a manifest, actual database/server UUID/version, and a
server-side marker before any fixture or schema setup. The manifest must be built
from inspected Docker metadata, not guessed. Database name alone is insufficient.
The QA server must use tmpfs, no persistent volume or operational mounts, and no
published MySQL port. Browser HTTP may be published on loopback by the disposable
PHP container; it receives no operational credentials or mounts.

Generated credentials and client option files belong outside the repository and
must be removed when the exact temporary resources are cleaned up. Do not commit
QA source archives, SQL dumps, browser session artifacts, or temporary secrets.

After verifying isolation:

1. Run `php tests/Support/mysql-prepare.php` in the QA container only.
2. Run `php vendor/bin/phpunit -c phpunit.mysql.xml` there.
3. `mysql-schema-review.php` rolls back the latest target migration, checks absence
   and readiness, then reapplies it **only on the disposable database**. It destroys
   synthetic history/finalization metadata, so do this before preparing browser data.
4. `mysql-browser-setup.php` creates synthetic QA accounts using a runtime-only
   random credential. `mysql-qa-router.php` verifies the server before each dynamic
   HTTP request. Login and form submissions are allowed only against this QA URL.
5. Shut down only uniquely named/labeled QA containers and networks after checking
   identities and mounts. Never run generic prune, volume deletion, or Compose reset.

Normal MySQL integration tests use transactions. Concurrency tests deliberately
commit unique synthetic fixtures so independent PHP processes can see them. They
use a readiness barrier, separate connection IDs, observed `FOR UPDATE` queries,
and controlled lock holding. This is targeted concurrency QA, not a load test.

## Academic decisions still required

The configured letter-to-point mapping is used; no numeric conversion thresholds
are invented. Official grade bands, repeat-course policy, KRS credit rules and risk
algorithm/provenance must be approved before operational use. Existing risk results
are displayed only when stored and operational filters accept their provenance.
Known demo markers are filtered; an unmarked synthetic import cannot be identified
automatically. Import governance remains necessary.

## Operational backup and deployment: NEEDS APPROVAL

1. Approve a maintenance window and a database backup to a protected destination.
   Record DB/server UUID, MySQL version, current volume mount, migration status,
   domain counts, and the release/source snapshot. Do not put credentials on the
   command line, in Git, or in reports.
2. With writes paused, take a consistent MySQL backup including schema, data and
   required database objects using protected client options. Verify exit status,
   file size and checksum. Check grants, triggers, routines and events separately.
3. Restore that **operational** backup into another authorized isolated MySQL 8.4
   server. Verify FK/index/type metadata, counts, domain fingerprints, migration
   status and application smoke tests. A successful synthetic restore in this QA
   phase does not prove the operational backup can be restored.
4. Validate the additive migration on the restored copy, including existing data
   conflicts. Ensure no long transactions or metadata-lock blockers remain.
5. Only after separate approval, deploy a schema-compatible release in maintenance,
   verify the actual target again, and apply the one pending migration. MySQL DDL
   is not an all-or-nothing transactional migration: inspect partial application on
   failure. Never blindly rerun it or reset the database.
6. Verify schema readiness, role guards, KRS periods in Asia/Jakarta and UTC audit
   times. Then perform approved operational smoke tests and resume writes.

Before any operational writes under the new workflow, code rollback may restore
the previous release while retaining additive schema. After history/finalization
data exists, do not use the migration's `down()` as a routine rollback: it deletes
audit information. Prefer a forward fix. Restoring a backup requires explicit
approval and reconciliation of all writes since the backup.

TIMESTAMP audit fields retain the existing design and its 2038 range limitation.
Registration windows are DATETIME wall times in Asia/Jakarta; audit instants use
UTC and are converted for display. No automatic type change is included here.

Windows bind mounts remain the main measured startup overhead. Moving the working
copy to WSL/Linux and changing operational mounts is a separate approved task,
requiring proof that the exact existing MySQL volume is retained. No such move or
mount change was made by this phase.
