# Shared hosting readiness

Assessment: 7 November 2026. **PARTIAL — provider/package not supplied or tested.**
This document derives requirements from the repository and locked dependencies;
it does not certify a hosting product. Docker and WSL are development tools, not
hosting requirements. No deployment or operational migration was performed.

## Requirements to verify with the hosting provider

| Area | Concrete requirement / check |
| --- | --- |
| PHP | Project requires PHP ^8.2; current lock includes packages bounded to PHP 8.5. Validate the exact target runtime using `composer check-platform-reqs --no-dev`. QA used PHP 8.2.34. |
| Extensions | Locked runtime dependencies require ctype, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl, pcre, session, tokenizer. Also verify PDO and pdo_mysql for the application database. Development SQLite tests require pdo_sqlite. |
| Composer | Composer 2 / lockfile install, production `--no-dev`; inspect lifecycle scripts. Do not use `composer setup`. No hosting Composer execution was tested. |
| Database | MySQL 8.4 is the verified QA target. Verify server version, privileges, charset/collation, JSON queries, indexes/FKs and timezone on the actual package. MariaDB compatibility is NOT VERIFIED. |
| Document root | Point to the release's `public/`. Keep `.env`, vendor, Git metadata, storage private files and QA archives outside the web root. Verify rewrite rules on Apache/LiteSpeed/Nginx rather than moving index.php blindly. |
| Assets | Upload the fresh `public/build` manifest and assets. Remove development `public/hot` from the release. Node/npm need not run on hosting if assets are built beforehand. |
| Permissions | Web PHP can read source; only storage and bootstrap/cache need application write access. Verify provider UID/group and restrictive ownership; do not use chmod 777. |
| Encryption | Provision a unique production APP_KEY securely. Never use the public PHPUnit key; preserve an established key when upgrading an existing installation. |
| Sessions | Default config uses database sessions; verify sessions table and correct permissions. Use secure cookies with HTTPS and a deliberate domain/SameSite policy. Do not use QA array sessions in production. |
| Cache | Default database cache needs its tables. If using file cache, verify private writable storage. Do not cache academic summaries without an invalidation design. |
| Queue | Default database queue needs jobs tables and a worker strategy if asynchronous jobs are introduced. No AI job/worker pipeline is implemented. A provider without workers needs a reviewed bounded cron strategy, not an assumed daemon. |
| Scheduler | routes/console.php currently defines no academic scheduled workflow. If scheduled work is added, confirm cron access and invoke schedule:run once per minute under the correct PHP binary. |
| Mail | Verify SMTP provider, credentials, TLS and outbound limits if mail is required; log transport is not delivery. No email-based recovery workflow is implemented. |
| Production errors | APP_ENV=production, APP_DEBUG=false, protected/redacted logs, log rotation, no QA routes/fixtures or archives in the release. |
| HTTP | HTTPS redirect, session secure flag, intended host/proxy config, request limits and security headers must be verified at the real server. Test CSP before enforcing because frontend compatibility is unverified. |
| Resources | Confirm memory, execution time, upload limits, storage/inodes, concurrent PHP workers, DB connections and CPU quotas using realistic synthetic load. No universal resource minimum is established by current QA. |
| Backup | Define encrypted off-server backups, retention, access control and recovery objectives. Rehearse restore and the pending migration on a separately approved copy before operational rollout. |

## Release gate

1. Verify the purchased hosting package against the requirements above, including
   filesystem document-root isolation and the actual PHP extensions.
2. Prepare an allowlisted release containing application source, installed runtime
   dependencies and built assets. Exclude `.git`, local QA artifacts/archives,
   SQLite fixtures, logs, node_modules, tests and development environment files.
3. Provision production secrets separately; check database/server identity before
   any approved migration. Review pending workflow migration and backup/restore
   results. MySQL DDL may leave partial changes after an error.
4. Validate login/logout, CSRF, role boundaries, KRS, grade finalization, summaries,
   assets, responsiveness and error handling on an approved staging package.
5. Publish only after separate deployment approval, with an explicit rollback and
   data reconciliation plan. Source rollback does not undo new academic records.

Remaining hosting gates: provider/package unknown; actual backup restore,
operational schema review and production server/security configuration remain
unverified. Local Chrome browser QA has since passed; see the follow-up below.
Deployment readiness on an actual hosting package remains unverified.

## Final system verification follow-up

The assessment above is a historical snapshot. Current local browser results and
remaining release gates are recorded in
[final-system-verification.md](final-system-verification.md). Windows Chrome and
an existing cached Playwright installation can be used from WSL without installing
packages or changing MCP configuration. This does not establish compatibility
with a purchased hosting package.

## Practical deployment checklist — execute only after separate rollout approval

- [ ] Record the provider/package, PHP CLI and web versions, extensions, database
  version, document root and resource limits. Verify the CLI and web PHP settings
  separately; run `composer check-platform-reqs --no-dev` on the target runtime.
- [ ] Prepare an allowlisted release from the reviewed source and lockfile.
  Exclude QA artifacts/screenshots, tests, Git, node_modules, temporary databases,
  local environment files, logs, Windows browser caches and `public/hot`. Prepare
  empty runtime directories; do not package local storage sessions, compiled views
  or `storage/framework/testing` fixtures.
- [ ] Install locked production dependencies in the release build environment,
  with lifecycle scripts reviewed and explicitly approved. Do not run
  `composer setup`, whose script includes migration. Build assets beforehand
  using the lockfile and include all files referenced by `public/build/manifest.json`.
- [ ] Point the site root to `public/`. Test rewrites and deny access to environment,
  Git, Composer, vendor, private storage and backup files through real HTTP.
- [ ] Provision production secrets privately. Preserve the established APP_KEY
  when upgrading, set APP_ENV=production/APP_DEBUG=false, use HTTPS and secure,
  HttpOnly session cookies. Never ship the public PHPUnit key or QA credentials.
- [ ] Verify PHP ownership: application code readable, only storage and
  bootstrap/cache writable by the application. Avoid world-writable permissions.
- [ ] Select session/cache/queue drivers supported by the package and verify their
  required tables/directories. A queue worker/cron is needed only for an actual
  configured asynchronous/scheduled workflow; do not invent an AI worker.
- [ ] Verify SMTP if delivery is required, error logging/rotation, request limits,
  upload/storage quotas, timezone, charset/collation and JSON/FK/index behavior.
- [ ] Rehearse an encrypted backup restore and pending migrations on a separately
  approved copy. Verify actual database/server identity and permissions before
  any separately approved operational migration. MySQL DDL may partially apply.
- [ ] On approved staging, verify all four roles, KRS decisions/history, final
  grades, IPS/IPK, actual assets, responsive layouts and private-file denials.
- [ ] Review source rollback and data reconciliation together, then obtain final
  deployment approval. No deployment command is run by this checklist.
