# UniNalar — Lecturer module development and QA

7 November 2026, Asia/Jakarta. **PARTIAL — functional QA PASS; visual browser QA
BLOCKED.** This report covers the defined lecturer workflows implemented in this
session, not production readiness or unspecified academic policies.

## 1. Workspace, Git and existing work

Active workspace `/home/hendra_huang/projects/UniNalar-main`, Linux WSL2 filesystem;
branch `main`, HEAD `02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`, remote
`https://github.com/Liwaru/UniNalar.git`. Existing uncommitted application/student
work was inventoried and preserved. No reset, checkout, pull, commit, push or deploy.
Old QA and Windows workspaces were not editing targets.

Laravel lock v12.70.0; PHP QA 8.2.34; native Node v24.21.0/npm 11.19.0; Vite 7.3.7.
Existing dependencies/images reused. No package/system/browser/MCP installation.
Composer/npm lock hashes match the prior session. Read the previous application,
student, hosting and relevant Phase 4–6 reports; historical counts were not treated
as current evidence for new code.

## 2. Lecturer pages

| Page | URL below /dashboard/dosen | Scope and current result |
| --- | --- | --- |
| Dashboard | (root) | Assigned-class and approved-student aggregates, ungraded/final counts, attendance-record count, semester filter, identity and links. FIXED; functional PASS. |
| Class catalog | /kelas | Owned classes, course/semester/code, approved count/capacity, search, semester filter, 15-row pagination and detail links. New; functional PASS. |
| Class detail | /kelas/{kelas} | Course/code/SKS, semester/class, capacity, official participants, ungraded/draft/final counts and related links. New; functional PASS. |
| Official roster | /kelas/{kelas}/peserta | Approved/taken participants only; NIM/name/program, KRS and grade status, search/status filter, 25-row pagination. New; functional PASS. |
| Grading | /nilai | Semester/class selection, participant search/status filter, bounded 25-row forms, draft edit/final lock and pagination. FIXED; functional PASS. |
| Attendance recap | /kelas/{kelas}/kehadiran | Read-only stored attendance for approved participants of the owned class; name/NIM/meeting filter, status totals and pagination. New; functional PASS. |
| Profile | /profil | Owned active lecturer identity/affiliation and contact-only update. New; functional PASS. |

Visual results for every page remain BLOCKED. There are 10 lecturer endpoints:
seven GET pages, existing grade PUT/finalization POST, and the new profile PUT.
All remain behind auth and level:2; class/grade identifiers have numeric constraints.

## 3. New pages

Added five pages: class catalog, detail, official roster, attendance recap and
profile. Added DosenKelasController and DosenProfilController. Reused the existing
shared dashboard shell and grading endpoints rather than making duplicate grade
or student pages. Three partials provide filters, state notices and class links.

## 4. Existing pages improved

Dashboard now includes identity, semester filtering, ungraded count and stored
attendance count; class shortcuts lead to scoped details. Existing unique-student
versus participant-per-class counting is labelled explicitly.

Grading previously paginated ten classes but eager-loaded all their participants.
It now selects one class and paginates its participants, rendering at most 25 grade
forms. Pagination pins the selected class. Validation input/revision is retained
only for the submitted participant, preventing the same failed value appearing in
other rows. Breadcrumbs and role-scoped focus/form/table styling were added.

## 5. Backend and database

LecturerAcademicData retains the common operational-class and approved-detail
queries. Added scoped semester/class choices, request validation and participant
search/status filtering. Readiness checks now cover metadata fields used by new
pages. Empty/missing profile/schema states do not require invented academic data.

No model, migration, seeder, operational schema change or new academic status was
needed. Existing restrictive relations, KRS approval requirements, grade locks and
historical-credit protections remain unchanged. No class-status column exists;
none was invented. Profile inactive/demo/unlinked behavior follows the existing
active operational-lecturer guard.

## 6. Navigation

Sidebar now links to Kelas Diampu, Profil Saya and Pengelolaan Nilai. Class routes
share the class active state; profile and grading have their own active states.
Details link to official participants, grading for that class and attendance recap.
Lecturer breadcrumb and global success/error feedback are included in the shell.
Dashboard list/grading links preserve the selected semester.

## 7. Forms and validation

Search: nullable string, maximum 255 characters. Semester/class IDs must belong to
the lecturer's operational class choices. Grade status filter accepts only existing
ungraded/draft/final meanings. Fixed class-roster routes reject conflicting class/
semester context. Attendance meeting filter is an integer 1–65535, matching storage.

Semester selection on the grading page is a separate GET form, so changing semester
does not submit an obsolete class ID. Class/search/status filtering preserves that
semester. All mutations use CSRF, explicit HTTP methods and server validation.
No new JavaScript dependency or custom client-side academic logic was introduced.

## 8. Security and authorization

Automated tests cover guest redirects, all seven lecturer read pages for wrong
roles, contact mutation forbidden to Student/Admin/Owner, unlinked profiles, foreign
class detail/roster/attendance 404s, malformed IDs and foreign class-filter rejection.
Existing foreign-grade and finalization ownership tests remain green.

Only NIM/name/program and required academic statuses appear in the roster; private
student contact and internal notes are not added. Header metadata is Blade escaped,
including dynamic subtitles. A stored script-like course name is displayed escaped
on all three class pages; a quote/SQL-like search term remains a bound value and
returns no matching classes. This is targeted verification, not a full pentest.

## 9. Grade entry

Preserved 0–100 numeric range, at most two decimals, explicit configured grade letter
and server-derived points. No numeric-to-letter bands or component weights invented.
Tests cover empty/malformed/array/out-of-range/overprecision values, 0/E, 100/A,
valid updates, stale revisions, unapproved and foreign participants. Grade-mutation
services retain role/ownership/transaction checks and existing throttles.

## 10. Finalization and locking

Existing finalization records the UTC actor/time and rejects repeats. The UI hides
ordinary edit controls after finalization and displays WIB time/lock state. Direct
HTTP edits/repeated finalization are rejected without changing the saved final
value. Concurrent edit/finalization and competing-edit tests remain green.

No reopening route or arbitrary correction authority added. Current metadata is
not a full immutable edit-history ledger; any new correction/audit workflow needs
an agreed design. Eloquent events do not protect arbitrary privileged SQL/bulk writes.

## 11. Integration with students

Students remain absent from the lecturer roster until KRS approval. Draft grades
remain distinct from final grades and do not enter student dashboard/GPA summaries.
Finalized grades update student grade/summary views using the existing GPA service.
The HTTP journey saves 90/A, edits to 95/A, rejects a stale edit, finalizes, rejects
a final overwrite, then verifies 95.00 and GPA 4.00 for the student.

All previous student tests were retained; no student controller/view was edited in
this lecturer session. Shared layout/CSS changes are conditional/scoped to lecturers.

## 12. Integration with Admin

Both automated and actual HTTP tests create/assign a class through the existing
Admin endpoint. The lecturer sees that assignment; pending KRS yields no official
participant. Following Admin approval, the participant appears. HTTP also exercises
rejection and a retained second submission version before approval.
No new permission allows Admin to use lecturer-only mutation endpoints.

## 13. Integration with Owner

After finalization, automated and HTTP tests confirm Owner's database-derived GPA
4.00 for the relevant synthetic program. Existing Owner filter/all-history GPA
semantics are retained; this session does not add report/export or redefine metrics.

## 14. Attendance status

Existing tables link attendance to KRS detail, meeting, date/status and optional
recorder. Official entry/correction/audit rules are not documented. Implemented the
explicitly permitted read-only recap, not mutation endpoints or new status enums.

A 26-record fixture produces 25 rows on page 1 and one on page 2; the status total
remains 26. Pending participants are excluded. Meeting filter/invalid meeting and
POST-to-read-only-route 405 are tested. No attendance percentage/pass threshold is
invented; student attendance calculations remain the existing configured behavior.

## 15. Lecturer profile

Implemented the contact-only pattern explicitly requested by comparison with the
student profile. Only email/phone are accepted for persistence. Email uniqueness,
format/length and reserved demo-domain checks apply; phone length is capped at 30.
Forged NIDN, user_id, program, active flag and role fields do not change academic
identity or privileges. Updating profile email does not change login credentials.

## 16. Responsiveness and accessibility

Kept the existing visual design/mobile expanded navigation. Added scoped input and
button sizing, visible keyboard focus, wrapping class links, metadata cards and
keyboard-focusable scroll regions for wide tables. No redesign or browser package
installation. Source/build/HTTP tests verify structure and asset responses only.

375×812, 390×844, 768×1024, 1366×768 and 1920×1080: NOT VISUALLY TESTED.
Computed contrast, actual overflow, keyboard traversal and mobile interactions
remain unverified. No responsive/accessibility certification is claimed.

## 17. SQLite results

PASS: **96 tests / 1,308 assertions**, 10.656 s. Prior baseline was 82 / 1,105.
The new lecturer feature class contains 14 scenarios / 203 assertions: eleven
shared with MySQL and three additional schema/input/query-scaling regressions.
Default Tests/TestCase verifies testing + SQLite :memory: before schema writes.

## 18. MySQL results

PASS: **37 tests / 445 assertions**, 12.849 s. Prior baseline was 26 / 266.
New synthetic MySQL 8.4 instance used tmpfs /var/lib/mysql, an internal QA-only
network, no published ports or persistent volume. Mount/network metadata, actual
UUID/version, fresh qa_ schema name and absence of operational schema were verified
before application writes. Existing manifest/hostname/marker guard also verified
before migration, tests and worker connections. No operational records copied.

## 19. Concurrency

PASS: **7 scenarios** included in the MySQL suite: competing submissions, last-seat
reservation, competing decisions, grade creation, finalization versus edit, stale
concurrent edits and protected class deletion. Independent worker connections and
FOR UPDATE were observed. The SHOW STATUS deadlock field is null/unavailable; zero
deadlocks or sustained-load performance is not claimed.

## 20. Actual HTTP end-to-end

PASS: **129 checks**, preserving the prior student journey and adding lecturer
pages/profile, Admin assignment, class ownership/IDOR, draft edit/stale revision,
final lock and Owner verification. Includes CSRF 419 responses, twelve cross-role
403 checks, logout/session checks, ten built-asset 200 responses and persisted
history/contact/approval/final-grade assertions.

The server bound only to container localhost without a published port/network.
Fixtures used a new verified empty temporary SQLite file; additional lecturer and
student fixtures reverified its actual identity before writes. Fixture setup used
testing; the HTTP process used local to keep CSRF active. Persisted assertions are
scoped to the intended synthetic student's KRS/grade, not an arbitrary first row.

## 21. Syntax, Pint, build and performance evidence

- PHP syntax: PASS, **108** active PHP files.
- Pint --test: PASS, **107** active source files; existing QA-archive exclusion kept.
- Vite: PASS, **127** modules, **3.98 s**. Dashboard CSS 275.60 kB / gzip 41.71 kB.
- Git diff --check: PASS. Lockfiles unchanged; no dependency upgrades.
- Query-scaling test: class catalog grows from 1 to 16 classes while allowing at
  most one additional query, detecting per-class lazy loads. Roster/grading/attendance
  use eager loading; no cache of academic values added.

Build emits the same Bootstrap @charset optimization warnings present in both
previous session logs. It exits successfully and assets serve correctly; it is
not a warning-free build. No unrelated dependency/build-pipeline refactor performed.
No paired response-time/load benchmark or broad performance improvement claimed.

## 22. Browser QA

Playwright MCP and Chrome DevTools MCP are not exposed in the current tool catalog.
Browser/visual QA: **BLOCKED**. No configuration/MCP/browser dependency installed.
HTTP fallback verifies server behavior/assets, not CSS rendering, console, JavaScript
execution, screenshots or real browser interactions. These remain required before
full visual acceptance.

## 23. Fixes and development failures resolved

- Missing lecturer class/roster/profile/attendance navigation: implemented scoped
  pages and real backend actions/read queries.
- Unbounded grade forms despite class pagination: participant pagination, stable
  class context and corresponding regression.
- Missing dashboard ungraded/attendance/semester context: database aggregates and
  explicit count semantics.
- Failed row input lost or liable to appear across forms: row-specific old input
  and revision preservation.
- Dynamic subtitle escaping: use escaped Blade output; stored-XSS regression.
- Partial schema metadata guard: cover fields used by new filters/views and test
  controlled empty/unavailable states.

Development test failures included fixture import mistakes and an HTTP assertion
using an unordered first row after adding another synthetic student. Imports and
assertion ownership scope were corrected; valid tests and application validation
were not removed or weakened. Final regression/HTTP runs passed after correction.

## 24. Remaining blockers and decisions

BLOCKED: visual browser/viewport checks listed above. No known automated functional
failure remains in the exercised lecturer scope.

NEEDS SPECIFICATION: attendance input/correction/recorder history and permitted
transitions; final-grade reopening/corrections; numeric-to-letter conversion or
component weights; any class-status lifecycle; extended grade-edit audit retention.
No fake controls or inferred institutional policy added to fill those gaps.

PARTIAL: large-volume class-option loading, sustained load, broader security review
and actual shared-hosting compatibility. Operational backup/migration/rollout remains
a separately authorized process and was not performed here.

## 25. Final verdict and changed files

**PARTIAL — defined lecturer workflows implemented and functional QA passed;
visual QA BLOCKED and unspecified academic mutation workflows NEEDS SPECIFICATION.**
Not a 100%-complete or production-ready claim.

Session source changes: DosenDashboardController, DosenNilaiController,
LecturerAcademicData, new DosenKelasController/DosenProfilController; lecturer
views/partials, dashboard navigation, lecturer sections of shared layout/CSS and
six new role-protected routes. Three lecturer test files and this report added;
README/application audit links updated. Existing student/Admin/Owner changes from
previous sessions remain pending and were not overwritten or attributed here.

Local evidence/harnesses: qa-artifacts/lecturer/ (ignored). Temporary MySQL resources
and credential files are removed by the harness. PHP/localhost HTTP QA container
and remaining QA processes are removed at cleanup; dependencies/build/evidence and
old archives remain. No operational DB access/migration/seeding, environment/Compose
edit, volume removal, system change, commit/push or public deployment occurred.
