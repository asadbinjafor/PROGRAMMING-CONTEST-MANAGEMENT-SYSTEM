# PCMS Project Tracker

Last updated: 2026-09-18  
Project: Programming Contest Management System (PCMS)  
Current phase: PHP-to-Oracle connection operational; live role-based HTTP integration verified

## 1. Purpose

This file is the working source of truth for future project work. It combines the database report, the exported Figma screens, confirmed gaps, design decisions that still need to be made, and a prioritized execution backlog.

Update this tracker whenever a task is started, completed, blocked, or materially changed.

## 2. Audited source material

| Source | Coverage | Status |
|---|---|---|
| [advance_data_base_report.pdf](./advance_data_base_report.pdf) | 73 pages: proposal, UML/activity diagrams, UI plan, ERD, normalization, schema, Oracle SQL/PL/SQL, sample data, conclusion | Fully reviewed |
| [ADBSMS Figma Design.pdf](./ADBSMS%20Figma%20Design.pdf) | 7 exported desktop screens: login, dashboard, contest details, problem statement, submission, leaderboard, clarification | Fully reviewed |
| Executable source code | PHP MVC application, Oracle install/reset/verify scripts, responsive UI, tests, configuration, and documentation | Implemented; OCI8 and live role-based HTTP verification pass |
| Native Figma file/link | No editable Figma source, component library, prototype, or design-token export is present | Missing |

Report section map:

- Pages 1-3: cover, contents, introduction, and proposal
- Pages 4-10: class, use-case, and activity diagrams
- Pages 11-17: the same seven screens exported separately in the Figma PDF
- Page 18: scenario and entity relationships
- Pages 19-24: ERD, normalization, and final schema diagram
- Pages 25-30: Oracle table DDL
- Pages 31-35: sample inserts
- Pages 37-50: basic PL/SQL exercises
- Pages 52-71: functions, procedures, records, cursors, triggers, and packages
- Page 73: conclusion

## 3. Current project definition

### Intended users

- Participant: register/login, browse contests and problems, submit solutions, see verdicts/rankings, ask clarifications, and read announcements.
- Organizer: create and manage contests, problems, test cases, announcements, and clarification replies.
- Administrator: manage users, approve/monitor contests, inspect submissions, and view system-wide results.

### Proposed database objects

| Object | Purpose | Main relationships |
|---|---|---|
| `CONTEST_USER` | Stores user identity, role, institution, rating, image, and creation date | Organizes contests; submits solutions; appears on leaderboards; asks clarifications |
| `CONTEST` | Stores contest description, schedule, visibility, status, and organizer | Has problems, leaderboards, announcements, and clarifications |
| `PROBLEM` | Stores statement, difficulty, and time/memory limits | Belongs to a contest; has test cases and submissions |
| `TEST_CASE` | Stores input, expected output, and hidden flag | Belongs to a problem |
| `SUBMISSION` | Stores source, language, verdict, execution metrics, date, user, and problem | Belongs to a participant and problem |
| `LEADERBOARD` | Stores solved count, penalty, and rank for a contest/user pair | Belongs to a contest and user |
| `ANNOUNCEMENT` | Stores contest announcement title, message, and date | Belongs to a contest |
| `CLARIFICATION` | Stores a question, optional answer, status, contest, and asker | Belongs to a contest and user |

### Existing design language

- Purple is the primary action/brand color; pale lavender is used for surfaces and selected states.
- Cards use rounded corners, light borders, generous spacing, and soft shadows.
- Tables, text status badges, and icon-supported summary cards are the main data patterns.
- The visual direction is clean and usable as a desktop baseline, but it is not yet a complete design system or end-to-end product flow.

## 4. Executive assessment

The report is a solid academic planning package and covers the expected ADBMS topics. Since the initial audit, the folder has gained a runnable Oracle schema/install workflow and a PHP MVC application. Oracle SQL*Plus verification passes, PHP 8.5 loads OCI8 3.4.1 with Instant Client 19.26, and live browser-to-database role flows pass against the local PCMS schema. Oracle XE 10g remains suitable only for the academic workstation, not production deployment.

The most important model gap is contest registration. The proposal, use case, activity diagram, contest `Register` button, and participant-count UI all require a many-to-many contest/user registration record, but no table represents it. This prevents the database from answering basic questions such as who registered, when they registered, whether registration was accepted, or how `1,256 Registered` was calculated.

The main cross-artifact problem is inconsistency. The database samples use PCMS, 2026 contests, and `Sum of Two Numbers`; several mockups use CodeElite, `CodeSprint 2025`, and `A. Two Sum`. Navigation, verdicts, footer names, dates, and sample results also change between screens. A canonical product vocabulary and seed dataset must be chosen before implementation.

## 5. Confirmed findings

### 5.1 Database and PL/SQL

| ID | Severity | Finding | Evidence / impact | Required resolution |
|---|---|---|---|---|
| DB-001 | Blocker | Contest registration is not modeled | Registration is promised in the proposal/use case and shown in the Figma CTA, but the final eight-table schema has no contest-user junction table | Add `CONTEST_REGISTRATION` with contest/user uniqueness, status, timestamps, and indexes |
| DB-002 | Blocker | `SEQ_ANNOUNCEMENT` is referenced but never created | `SP_ADD_ANNOUNCEMENT` uses `SEQ_ANNOUNCEMENT.NEXTVAL`; the report contains no `CREATE SEQUENCE` | Create the sequence or use an identity column consistently |
| DB-003 | Blocker | A trigger contains a typographic closing quote | `TRG_NEW_SUBMISSION_ROW` uses `NVL(:NEW.Verdict, 'NOT SET’)`, which will not compile as normal Oracle SQL | Replace it with an ASCII quote and compile-test the trigger |
| DB-004 | High | Basic PL/SQL Q4 does not implement its prompt | The prompt says `UPCOMING` or `ONGOING`; the condition tests `COMPLETED` or `ONGOING` | Correct the condition and use a seed contest that proves both true/false branches |
| DB-005 | High | Required relationships are nullable in DDL | Organizer, contest, problem, user, and other FKs are nullable even though the scenario repeatedly says “exactly one” | Add appropriate `NOT NULL` constraints |
| DB-006 | High | Domain values are unrestricted strings | Role, visibility, contest status, difficulty, hidden flag, language, verdict, and clarification status accept arbitrary text | Add `CHECK` constraints or lookup tables with one canonical vocabulary |
| DB-007 | High | Schedule invariants are not enforced | Nothing prevents `EndTime <= StartTime`, non-positive duration, or invalid time/memory limits | Add checks and decide whether duration is calculated or stored |
| DB-008 | High | Password examples are stored as plaintext | Seed values include `roman123`, `asad123`, and similar strings | Store only a strong password hash; never seed real/plain passwords |
| DB-009 | High | Seed status values are stale relative to dates | Contests dated July 2026 remain `ONGOING`/`UPCOMING` in a PDF created 2026-08-01; status can contradict schedule | Derive status where possible or validate/update it transactionally |
| DB-010 | Medium | Global scheduling lacks time-zone semantics | Oracle `DATE` stores no time-zone offset although the proposal describes a broad online platform | Use `TIMESTAMP WITH TIME ZONE` or document one enforced platform zone |
| DB-011 | Medium | Audit/history fields are insufficient | Clarifications have no asked/answered timestamps or answerer; announcements have no author; submissions have no judge lifecycle timestamps | Add creator/answerer IDs and `created_at`/`updated_at`/`answered_at` fields as needed |
| DB-012 | Medium | Derived leaderboard data can drift | `SolvedProblemCount`, `Penalty`, and `RankPosition` are stored without a demonstrated synchronization rule | Prefer a query/view or define an atomic refresh procedure and invariants |
| DB-013 | Medium | Foreign-key access paths are not indexed | Oracle does not automatically index all FKs; common contest/problem/user queries may degrade | Add indexes for FKs and common sorting/filter columns |
| DB-014 | Medium | IDs are manually assigned inconsistently | Inserts hard-code IDs while one procedure expects a sequence | Standardize on identities/sequences for every generated primary key |
| DB-015 | Medium | Business-role integrity is not enforced | Any user can be an organizer FK; the rating trigger fires for every user, not only participants | Enforce role rules in controlled procedures/triggers or redesign roles as memberships |
| DB-016 | Medium | Normalization proof is incomplete | Pages 20-23 split relationships without declaring candidate keys and functional dependencies; derived attributes are not discussed | Document keys/FDs and justify every 1NF→2NF→3NF decomposition |
| DB-017 | Medium | Contest titles are used as if unique | Multiple PL/SQL examples handle `TOO_MANY_ROWS`, but title uniqueness is not guaranteed | Query by ID or add a documented business key/slug |
| DB-018 | Medium | Real judging detail is not modeled | Only one final verdict/metrics pair is stored; no per-test result, compiler/runtime version, judge message, or queue state exists | Decide academic vs working-judge scope; add judge entities only if the application will execute code |
| DB-019 | Low | Sample coverage is too shallow | Five contests have one problem and one test case each, limiting relationship and aggregation tests | Add multi-problem, multi-test, multi-user, duplicate-attempt, and empty-result fixtures |
| DB-020 | Low | Delete/update behavior is unspecified | No cascade/restrict policy is described for parent records | Document lifecycle rules and implement explicit FK actions/procedures |

### 5.2 Figma and product UX

| ID | Severity | Finding | Evidence / impact | Required resolution |
|---|---|---|---|---|
| UI-001 | High | Branding is inconsistent | Screens alternate between `PCMS`, `CodeElite`, and `CodeElite Systems` | Choose one product name and replace all logos, headers, and footers |
| UI-002 | High | Design data does not match database data | Figma uses `CodeSprint 2025`, `A. Two Sum`, rank `#128`, and 1,256 participants; SQL seeds different contests/problems and cannot count registrations | Create one canonical seed-data contract shared by designs and SQL |
| UI-003 | High | Essential organizer/admin screens are absent | All seven exported screens are login or participant-oriented despite organizer/admin being core actors | Design their dashboards and CRUD/review flows before front-end build |
| UI-004 | High | Core participant routes are incomplete | No contest list, problem list, registration confirmation, announcement list/detail, clarification inbox/detail, profile, settings, signup, or password-reset screens | Complete the participant flow map and missing screens |
| UI-005 | Medium | Navigation changes between screens | Dashboard uses a left rail and `Leaderboard`/`Clarifications`; inner pages use a top bar and `Rankings`/`Ask Questions`; some omit Dashboard | Define one responsive information architecture and canonical labels |
| UI-006 | Medium | Screen-to-screen results conflict | Dashboard shows Graph BFS as accepted; submission screen shows runtime error. Verdict label casing/abbreviations also vary | Reuse the same sample records and status component everywhere |
| UI-007 | Medium | Contest naming and dates conflict | `CodeSprint 2025` is displayed with a 30 July 2026 schedule; dashboard events use May/June 2025 | Align name/year/schedule and define date formatting/time zone |
| UI-008 | Medium | Problem example conflicts with the SQL model | Figma shows Two Sum with target/index behavior; database problem 201 is simple addition with input `12 30` and output `42` | Pick one problem fixture and update statement, sample, test cases, and submissions together |
| UI-009 | Medium | No interaction/state specification exists | Hover, focus, disabled, loading, empty, validation, success, failure, judge-pending, and offline states are absent | Add component states and annotated behavior/prototype links |
| UI-010 | Medium | Only desktop layouts are shown | Frames vary in size and there are no tablet/mobile breakpoints | Define responsive behavior for navigation, tables, editor, cards, and forms |
| UI-011 | Medium | Accessibility is not verified | Some secondary text is very light/small; icon-only controls and keyboard/focus behavior are unspecified | Run contrast checks, define focus states, labels, touch targets, and keyboard flow |
| UI-012 | Low | Frame height and whitespace are inconsistent | The submission page has a large unused lower region; other pages use different content widths/heights | Use a shared grid, max-width, vertical rhythm, and footer behavior |
| UI-013 | Low | Copyright/footer year is stale | All mockups show 2024 while project data targets 2026 | Make the year current/dynamic and standardize footer links |

### 5.3 Documentation and deliverability

| ID | Severity | Finding | Evidence / impact | Required resolution |
|---|---|---|---|---|
| DOC-001 | Resolved | Executable database deliverable was initially absent | Implemented ordered Oracle scripts, repeatable install/reset, verification, and negative tests | Completed 2026-09-15 |
| DOC-002 | Resolved | Execution evidence was initially absent | Clean install, zero-invalid-object check, query verification, negative tests, PHP lint, and smoke-test evidence are recorded in `docs/verification-report.md` | Completed 2026-09-15 |
| DOC-003 | Medium | Important diagrams are hard to read | Complete activity/class/ER diagrams are scaled down; the ERD uses small light text on black and many crossing lines | Re-export vector/high-resolution diagrams and simplify layout |
| DOC-004 | Medium | SQL readability is weak | Much of the DDL/PLSQL is presented as dense paragraph-like lines | Use monospaced code blocks with stable indentation and page breaks |
| DOC-005 | Low | The report contains blank pages | Pages 36, 51, and 72 are empty | Remove blank pages and refresh the contents/page numbers |
| DOC-006 | Medium | Claims exceed supplied evidence | The conclusion describes a working system, but the folder provides design/report artifacts only | Reword as a designed/prototyped database or add runnable implementation evidence |

## 6. Decisions required before implementation

Do not silently mix these choices. Record each decision here with date and owner.

| Decision | Options | Recommended default | Status |
|---|---|---|---|
| Delivery scope | ADBMS database submission only / full web application / full app plus secure code judge | Full PHP web application with safe academic/manual judging; isolated automatic judge deferred | Closed 2026-09-15 |
| Product brand | PCMS / CodeElite / another name | PCMS, because it matches the report title and most screens | Closed 2026-09-15 |
| Canonical demo contest | Report’s 2026 university contests / Figma’s CodeSprint fixture | PCMS CodeSprint 2026 across application and Oracle seed data | Closed 2026-09-15 |
| Oracle version | 19c / 21c XE / 23ai Free / course lab version | Match the faculty/lab environment | Open |
| Primary-key strategy | Identity columns / named sequences | Named sequences for Oracle 10g compatibility and course visibility | Closed 2026-09-15 |
| Duration | Stored number / calculated from schedule | Calculated from start/end timestamps in `V_CONTEST_STATUS` | Closed 2026-09-15 |
| Leaderboard | Stored rows / calculated view / cached snapshot | Calculated `V_LEADERBOARD` view | Closed 2026-09-15 |
| Time handling | One documented zone / per-contest time zone | `TIMESTAMP WITH TIME ZONE`; canonical fixture uses Asia/Dhaka | Closed 2026-09-15 |
| Roles | One role per user / role membership table | `ROLE` plus `USER_ROLE` membership | Closed 2026-09-15 |
| Delete policy | Restrict / soft delete / cascade by entity | Restrict core contest history; soft-delete user-visible content where required | Open |

## 7. Prioritized work backlog

Legend: `[ ]` not started, `[~]` in progress, `[x]` complete, `[!]` blocked.

### P0 - Make the database real and reproducible

- [x] P0-01 Create a `README.md` with scope, Oracle version, prerequisites, install order, and verification commands. Depends on: scope and Oracle-version decisions.
- [x] P0-02 Extract PDF SQL into `database/01_schema.sql`, `02_constraints_indexes.sql`, `03_seed.sql`, `04_program_units.sql`, and `05_verify.sql`.
- [x] P0-03 Add a safe reset script for only this project’s objects; list exact objects before dropping them.
- [x] P0-04 Add `CONTEST_REGISTRATION` (`registration_id`, `contest_id`, `user_id`, `status`, `registered_at`, optional cancellation/approval fields) with `UNIQUE(contest_id,user_id)`.
- [x] P0-05 Choose identities or create every required sequence, including the missing announcement sequence.
- [x] P0-06 Fix the smart quote in `TRG_NEW_SUBMISSION_ROW` and the status logic in Basic PL/SQL Q4.
- [x] P0-07 Add required `NOT NULL`, `CHECK`, time-bound, and positive-number constraints.
- [x] P0-08 Replace plaintext password seeds with clearly synthetic password hashes or remove authentication secrets from database fixtures.
- [x] P0-09 Compile all tables, sequences/identities, functions, procedures, triggers, and packages in a clean schema; require zero invalid objects.
- [x] P0-10 Run verification queries and capture deterministic results plus expected failures.
- [~] P0-11 Choose a canonical PCMS fixture dataset and synchronize the report and Figma copy with it. Application/seed complete; source PDFs remain unchanged.

### P1 - Correctness, performance, and lifecycle

- [x] P1-01 Decide and implement calculated/stored rules for contest duration, contest status, solved count, penalty, and rank.
- [x] P1-02 Add indexes for every FK and common access paths: contest schedule/status, problem by contest, submission by user/problem/date, clarification by contest/status, and leaderboard ordering.
- [x] P1-03 Add clarification `asked_at`, `answered_at`, and `answered_by_user_id`; enforce answer/status consistency.
- [x] P1-04 Add announcement author and audit timestamps.
- [x] P1-05 Add `created_at`/`updated_at` standards and move schedule/event times to the chosen timestamp policy.
- [x] P1-06 Enforce organizer/participant permissions through controlled procedures or role memberships.
- [ ] P1-07 Define contest lifecycle transitions and registration windows; reject illegal transitions.
- [ ] P1-08 Define parent deletion/archive rules and protect historical submissions/results.
- [ ] P1-09 Expand seeds to exercise one-to-many and many-to-many behavior, ties, duplicate submissions, unanswered questions, and empty contests.
- [ ] P1-10 Add transaction-safe procedures for registration, submission recording, clarification answering, and leaderboard refresh if stored.
- [x] P1-11 Add explicit exception handling and tests to package procedures that fetch by ID.
- [ ] P1-12 If judging is in scope, design `LANGUAGE_RUNTIME`, `JUDGE_RUN`, and per-test result storage plus queue/sandbox boundaries.

### P1 - Complete and unify the design

- [x] UX-01 Rename every screen/component/footer to the selected brand.
- [x] UX-02 Build a small token sheet: colors, typography, spacing, radius, shadows, breakpoints, and status semantics.
- [x] UX-03 Standardize navigation and labels (`Rankings` vs `Leaderboard`, `Clarifications` vs `Ask Questions`).
- [~] UX-04 Update all seven existing screens to use the canonical database fixture. Application complete; exported/native Figma remains unchanged.
- [x] UX-05 Design participant contest list/search/filter and contest-registration confirmation/cancellation states.
- [x] UX-06 Design problem list/filter and complete problem detail, including sample output, constraints, and limits.
- [x] UX-07 Design submission pending/judging/compile-error/detail flows and source-code validation.
- [x] UX-08 Design announcements and clarification inbox/detail views.
- [x] UX-09 Design signup, password reset, profile, and settings flows—or remove their links from the MVP.
- [x] UX-10 Design organizer screens for contest/problem/test-case/announcement/clarification management.
- [x] UX-11 Design administrator screens for users, approvals, monitoring, and reports.
- [~] UX-12 Add loading, empty, error, success, disabled, hover, focus, and destructive-confirmation states. Core states complete; async judge loading remains deferred.
- [x] UX-13 Add tablet/mobile variants and document table/editor overflow behavior.
- [~] UX-14 Run WCAG 2.2 AA contrast, keyboard, focus-order, label, and target-size review. Structural and authenticated visual review complete; full automated audit remains.
- [!] UX-15 Obtain the editable Figma file/link and create named reusable components with variants. Blocked: only a PDF export is available.

### P2 - Report quality

- [ ] DOC-07 Replace low-resolution diagrams with readable vector exports.
- [ ] DOC-08 Revise normalization with candidate keys, functional dependencies, and explicit 3NF reasoning.
- [ ] DOC-09 Insert the new registration entity consistently into scenario, class diagram, ERD, schema, DDL, and examples.
- [ ] DOC-10 Reformat every SQL/PLSQL example as copyable code with `/` delimiters where required.
- [ ] DOC-11 Add setup/run evidence, object-status query, expected output, and negative-test results.
- [ ] DOC-12 Remove blank pages and refresh page numbering/contents.
- [ ] DOC-13 Reconcile terminology, dates, problem names, statuses, and screenshots throughout.
- [ ] DOC-14 Revise the conclusion so it accurately states what was implemented and verified.

### P2 - Application implementation, only if selected

- [x] APP-01 Choose and document frontend, API, Oracle driver, authentication, and deployment stack.
- [x] APP-02 Define route/action contracts and authorization matrix for participant, organizer, and admin actions.
- [~] APP-03 Implement authentication without exposing password hashes; add secure session, reset, rate-limit, and audit behavior. Core behavior complete; production mail and shared throttling remain deployment work.
- [x] APP-04 Implement read flows: contests, problems, announcements, submissions, clarifications, and leaderboard.
- [x] APP-05 Implement write flows through validated transactions: registrations, submissions, contest management, and replies.
- [x] APP-06 Implement design tokens/components and the approved responsive screens.
- [~] APP-07 Add unit, integration, end-to-end, accessibility, and authorization tests. Lint, static, Oracle, live role dashboard/page, and representative 403 authorization checks pass; mutation-path and automated accessibility coverage remain.
- [x] APP-08 Keep code execution outside the PHP/Oracle host; current academic mode uses authorized manual review only.

## 8. Verification plan

### Database acceptance checks

- A fresh, empty Oracle schema installs from one documented command/path.
- A second install either succeeds idempotently or fails with a clear reset instruction.
- `USER_ERRORS` and object status queries show zero invalid project objects.
- All foreign-key columns required by the scenario reject nulls.
- Invalid roles, statuses, verdicts, flags, dates, negative limits, and duplicate registrations are rejected.
- A participant cannot register twice for the same contest.
- A submission cannot reference a nonexistent user/problem or violate the chosen registration rule.
- Clarification answer and status remain consistent.
- Leaderboard calculation handles multiple attempts, wrong-attempt penalties, ties, and users with zero solves.
- Dates/statuses remain consistent around start/end boundaries and the documented time zone.
- Every function, procedure, trigger, cursor example, and package has at least one positive and one relevant negative test.

### Design acceptance checks

- One brand, one navigation model, one terminology set, and one fixture dataset appear across all screens.
- Every visible CTA has a destination and defined success/error/cancel behavior.
- Participant, organizer, and admin journeys can each be completed in the prototype.
- Desktop, tablet, and mobile layouts are defined for data-heavy screens.
- Contrast, keyboard focus, form labels/errors, icon names, and touch targets meet WCAG 2.2 AA expectations.
- Loading, empty, partial, pending, error, and permission-denied states are designed.

### Report acceptance checks

- All diagrams agree with the final DDL.
- Every SQL block can be copied without typographic quotes or formatting corruption.
- Object creation order and test output are included.
- Table of contents, headings, captions, references, and page numbers are accurate.
- No blank pages or unreadably scaled figures remain.

## 9. Suggested milestone sequence

| Milestone | Outcome | Exit criteria |
|---|---|---|
| M0 - Scope lock | Product name, delivery scope, Oracle version, data fixture, and open modeling decisions are approved | All Section 6 decisions closed |
| M1 - Database baseline | Version-controlled schema, constraints, seed, program units, and verification scripts exist | P0 complete; clean install and zero invalid objects |
| M2 - Model hardening | Registration, audit data, lifecycle, indexes, and leaderboard rules are correct | Relevant P1 database checks pass |
| M3 - UX completion | Unified design system and full role-based flows are available | All P1 UX items complete and prototype reviewed |
| M4 - Final ADBMS report | Report matches the runnable database and includes evidence | P2 documentation acceptance checks pass |
| M5 - Application MVP | Optional web application implements the approved flows | Security, accessibility, integration, and E2E gates pass |

## 10. Progress log

| Date | Change | Result | Next action |
|---|---|---|---|
| 2026-09-15 | Audited the complete 73-page report and all 7 exported Figma screens; created this tracker | Discovery complete; blockers and prioritized backlog documented | Close the scope/brand/Oracle/data decisions, then start P0-01 and P0-02 |
| 2026-09-15 | Built the PHP/Oracle application foundation, role workflows, responsive PCMS UI, executable SQL, tests, and documentation | Oracle clean install passed with zero invalid objects; 5 negative DB tests, PHP lint, and 68 smoke assertions passed | Install a PHP 8.x-compatible Oracle 11.2+ client/server and OCI8, then run live role-based HTTP E2E and final visual/accessibility audit |
| 2026-09-18 | Reviewed all 5 slides and the full embedded 23:33 connection tutorial; configured PHP OCI8 3.4.1 with Instant Client 19.26 and connected PCMS to Oracle XE | Live schema queries, 4 public pages, 3 role dashboards, 3 role pages, and 3 authorization boundaries passed; pagination bind and view-data shadowing defects fixed | Add rollback-based mutation E2E, automated WCAG scan, and production deployment services |

## 11. Tracker maintenance rules

1. Assign a task ID from this file to every future commit or work session.
2. Change `[ ]` to `[~]` only when work has actually begun and add the owner/date in the progress log.
3. Change `[~]` to `[x]` only after the relevant acceptance checks pass.
4. Mark `[!]` only with a concrete blocker and the decision/input needed to clear it.
5. Add newly discovered defects to Confirmed Findings before adding implementation tasks.
6. Keep canonical names, statuses, dates, and sample IDs in one fixture specification; do not hand-edit them independently in SQL, Figma, and the report.
7. Never treat a screenshot or PDF code sample as executable proof. Keep copyable source and repeatable verification output in the repository.
