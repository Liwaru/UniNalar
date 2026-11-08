# UniNalar — Student module completion

7 November 2026, Asia/Jakarta. Final status: **PARTIAL — functional QA PASS;
browser/responsive visual QA BLOCKED**. This is the follow-up to
[the previous application audit](development-completion-audit.md), not a claim
that production rollout or every future academic workflow is complete.

## 1. Workspace and revision

Workspace `/home/hendra_huang/projects/UniNalar-main`, WSL2 Linux filesystem;
branch `main`, commit `02a2be7d7e181242c464490d1e6ff1bcc7e17e0f`, remote
`https://github.com/Liwaru/UniNalar.git`. All pre-existing uncommitted work was
preserved. No pull, branch switch, commit, push or deployment.

Existing local vendor/node_modules and Docker images were reused. No dependency
install, system install, operational environment/Compose change or operational
DB access was required. Composer/npm lock hashes remain unchanged.

## 2–5. Student pages, working behavior, fixes and additions

All eight existing student pages remain at their existing routes. No standalone
page was added: history/catalog/factors/IPS-IPK remain embedded in their related
pages, consistent with existing navigation. Two reusable Blade partials were added
for semester filters and grade summary.

| Page | Route suffix under /dashboard/mahasiswa | Implementation / verification | Status |
| --- | --- | --- | --- |
| Dashboard | (root) | Identity/program/semester, final numeric average/trend, KRS status, course count and SKS, IPS/IPK, valid attendance summary, stored risk, owned recommendation/follow-up counts. Missing schema handled without 500. | FIXED; functional PASS |
| Profile | /profil | Existing identity fields and contact-only update retained. Real HTTP update persisted; validation, role/ownership and mass-assignment regression tests retained. Semester context/breadcrumb added through shared shell. | PASS for exercised scope |
| KRS & classes | /krs | Historical records remain visible without an active semester or write-ready workflow. Catalog labels remaining seats/full classes and disables full choices; server remains authoritative. | FIXED; functional PASS |
| Submission/revision history | embedded in /krs | All owned semester histories displayed, including rejected version notes and immutable snapshots. Readability separated from mutation readiness. | FIXED; functional PASS |
| Grades and IPS/IPK | /nilai | Draft/final labels; explicit unknown finalization status for legacy schema; semester filter, 25-row pagination; same all-history IPS/IPK service as dashboard. | FIXED; functional PASS |
| Attendance | /kehadiran | Owned approved enrollments/taken details only; semester filter, 25-row pagination and summary across all filtered records, not merely the current page. | FIXED; functional PASS |
| Risk and explanatory factors | /analisis-risiko | Owned stored results/factors, model version/source if recorded, WIB timestamp, validated semester filter and 25-row pagination; no invented scores. | FIXED; functional PASS |
| Recommendations | /rekomendasi | Owned analyses, semester filter/pagination; a factor from a different analysis cannot disclose its explanation through an owned recommendation. | FIXED; functional PASS |
| Follow-ups | /tindak-lanjut | Owned read-only records, semester filter/pagination, WIB timestamp or unscheduled state. Internal notes remain hidden. | PASS for exercised scope |
| Notifications / new academic actions | none | No approved design/workflow found; no synthetic notifications or new mutation authority introduced. | NEEDS SPECIFICATION |

All visual/responsive statuses remain BLOCKED irrespective of functional status.
The existing attendance status aliases/minimum-record setting and grade scale were
retained; no new academic percentages, thresholds, weights or permissions were made.

## 6. Buttons and forms exercised

Real HTTP QA exercised login/logout, contact validation/update, add class, remove
class, submit KRS, duplicate submission validation, Admin rejection, student
resubmission as version 2, Admin approval, lecturer draft grade entry/finalization,
and student confirmation of final grade/GPA. Database assertions verified persisted
contact, two history versions, approval and final grade.

Automated feature/integration tests exercised semester filters, pagination page 2,
invalid/unknown/demo/array semester IDs, full-class labels and forged selections.
The browser engine did not execute JavaScript or click controls; these are HTTP
and framework tests, not visual browser interaction results.

## 7. Navigation

All eight student pages returned HTTP 200 after authenticated QA login. Existing
sidebar role links and active-route regression tests pass. Added breadcrumb and
skip link; the latter targets the main content. No route or menu was invented for
an unspecified workflow. The numeric detail route constraint now returns 404 for
malformed KRS detail identifiers rather than reaching an int parameter.

Shared-shell edits are student-conditional where relevant. The mobile navigation
continues to use the existing expanded menu layout; no new collapse behavior was
introduced. Scrollable keyboard-focusable table regions keep wide tables inside
the card. Form controls and focus styles were improved using existing colors.
These source changes are not a substitute for viewport checks.

## 8. Authorization and IDOR

Authentication/role middleware retained. Existing route authorization/profile/KRS
ownership tests remain green. New tests prove that risk/recommendation/follow-up
queries and counts exclude another student's records, attendance excludes another
student's status, and a malformed cross-analysis factor link cannot leak a private
explanation. Known demo records/semesters remain excluded.

The factor issue came from two independently valid foreign keys without a composite
constraint tying recommendation and factor to the same analysis. The student read
path clears an inconsistent factor relation before rendering; no operational data
was edited. This does not claim that malformed legacy rows were repaired or that
all possible direct SQL writes are prevented.

Real HTTP retained CSRF verification by using a localhost-only QA process outside
the testing environment after synthetic fixture setup. Missing CSRF requests
returned 419; twelve cross-role dashboard requests returned 403; logout redirected
subsequent protected access to login. This is not a full penetration test.

## 9. KRS verification

Current automated coverage includes explicit WIB registration windows, closed or
missing active semester, available/full classes, configured SKS limit, duplicate
courses/submissions, rejection, revision/version history, immutable snapshots,
approval, foreign detail IDOR, transaction rollback and concurrent reservations.

New regressions specifically prove:

- Closed/no-active-semester does not erase the student's displayed history.
- Legacy KRS remains readable when the history table is unavailable.
- Existing history is readable when its unique-version index is missing, while
  mutation remains disabled by the existing readiness guard.
- Full classes are labelled; forged POST selection is rejected and no partial
  KRS row remains.

No history rows were deleted. No new KRS opening rule or operational migration.
Current catalog/history are not independently paginated; the five transactional
result pages below are paginated. Extensive historical-record performance still
requires realistic volume measurement.

## 10. Grades, IPS/IPK and historical credits

Shared AcademicGradeSummary remains the single source for both student summary
locations. Only valid final grades from approved enrollments contribute to GPA;
configured SKS weighting/latest-attempt behavior is unchanged. Existing tests for
historical SKS protection, multi-semester grades, invalid/draft grades and final
locking remain green.

The grade table filter intentionally does not redefine the all-history latest IPS
or IPK. Its explanatory label and tests verify that an empty selected semester can
show an empty table while retaining the valid all-history GPA. Drafts retain their
existing explicit status. Legacy grades without finalization schema are not silently
labelled final and do not enter dashboard summaries.

## 11. Attendance

Dashboard and detail page now use the same approved/taken-enrollment scope and
existing alias/minimum-data rules. Unknown status records are counted separately
and excluded from the percentage. Empty/insufficient valid data yields null,
never division by zero or a fabricated attendance rate.

A 26-meeting fixture (25 present, one alpha) produces 96.2% on both dashboard and
detail. Page 1 has 25 rows and page 2 one row, while the summary remains based on
all 26 records. Rejected enrollment disappears from the attendance scope. No
attendance input/correction authority or institutional attendance threshold added.

## 12. Risk, recommendations and follow-ups

Stored results only. Source/model version are shown when recorded; absence is
labelled explicitly. Explanations and all displayed text remain Blade escaped.
No risk scoring engine, inference call or generated recommendation was activated.

26 owned results/recommendations/follow-ups span two pages; another student's
records and internal notes remain absent. Dashboard counts match owned records.
Recommendation query-scaling regression compares 2 versus 25 records and allows
at most one additional query, catching a per-row factor/semester load.

Student mutation/approval/closure of follow-ups is not implemented because no
approved student action or state transition was found. Current source defines
read-only visibility, which is preserved.

## 13–16. Automated results

| Check | Current result |
| --- | --- |
| SQLite baseline supplied by prior session | HISTORICAL: 71 tests / 946 assertions |
| SQLite full current suite | PASS: 82 tests / 1,105 assertions; 6.862 s |
| New SQLite student module class | PASS: 11 scenarios, including the shared 7 MySQL scenarios plus legacy/schema/query regressions |
| MySQL full current suite | PASS: 26 tests / 266 assertions; 9.117 s |
| Concurrency | PASS: 7 scenarios included in MySQL suite; separate connections and FOR UPDATE observed |
| HTTP end-to-end | PASS: 95 checks, including 8 pages, role/CSRF/session checks, revision/approval/grading journey, 10 built-asset responses and persisted QA state |
| PHP syntax | PASS: 103 active PHP files; last two edited PHP files also verified by execution/formatting and rechecked at cleanup |
| Pint --test | PASS: 102 active PHP files; historical QA artifacts excluded by pre-existing pint.json |
| Vite build | PASS: 127 modules, 2.64 s, native Node/npm; all 10 manifest entries served HTTP 200 |
| Git whitespace | PASS: git diff --check |

Existing tests were not removed or weakened. MySQL initially exposed two fixture
mistakes: an overlong synthetic follow-up status and an assertion that counted
histories from the entire suite rather than the tested KRS. Fixtures/assertion
scope were corrected; application constraints stayed intact and suites reran.

## 17. Browser QA and responsive matrix

Playwright MCP and Chrome DevTools MCP are absent from the exposed tool catalog.
No browser configuration/system software was changed to work around their absence.
Browser/visual QA: **BLOCKED**. HTTP fallback runs actual PHP requests and verifies
assets, sessions, CSRF and persisted state, but does not evaluate CSS/JS rendering.

NOT VISUALLY TESTED: 375×812, 390×844, 768×1024, 1366×768 and 1920×1080.
Screenshots, computed contrast, actual overflow, keyboard traversal, chart rendering,
font loading and mobile-menu interaction have no current browser evidence.
No claim of 100% responsiveness/accessibility is made.

## 18–19. Remaining issues and specifications

- BLOCKED: browser/viewport QA as described above. No known failing automated
  student workflow remains in the exercised suite.
- NEEDS SPECIFICATION: risk/model approval and evaluation, legitimate inference
  provenance contract, automated recommendations, student follow-up actions,
  notifications and attendance entry/correction authority. Displaying stored
  results is implemented; these processing/mutation workflows are separate.
- NEEDS SPECIFICATION: reopening final grades or historical credit corrections;
  no new authority or academic algorithm was assumed.
- PARTIAL: very large history/catalog load testing and broader security assessment.
- Operational schema/hosting rollout remains a separate approved process; this
  session does not verify current operational migration state or deploy changes.

## 20. Verdict and evidence

**PARTIAL — the defined student read/profile/KRS workflow has current functional
QA evidence; visual QA remains BLOCKED and unspecified academic actions remain
NEEDS SPECIFICATION.** No production-readiness or 100%-complete claim.

Student-session changes: MahasiswaDashboardController, MahasiswaKrsController,
MahasiswaOperationalData, AcademicGradeSummary, student Blade pages and two partials,
student CSS, shared layout, numeric student-detail route; three new test files,
this report and documentation links. Changes already pending before this task in
other modules were retained rather than attributed to the student work.

Ignored evidence/harnesses: `qa-artifacts/student/`. MySQL used new tmpfs storage,
an internal network, no published ports and a fresh qa_ schema. Container mounts,
network, UUID/version, absence of operational schema and the application manifest/
marker guard were checked before writes. HTTP used a new verified empty synthetic
SQLite file under /tmp, then verified actual file identity before final assertions.
No operational secrets or records were copied. No operational migration, seed,
backup, volume removal, Compose edit or deployment.

All disposable QA resources created here are removed at cleanup; source, dependencies,
build output and ignored evidence are retained. Existing operational containers and
old archives/workspaces are preserved. No commit/push was made.
