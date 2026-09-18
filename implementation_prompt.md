# Master Implementation Prompt - PCMS Full-Stack Application

Copy everything below this line and give it to the coding agent that will build the project.

---

You are the lead full-stack engineer responsible for turning this repository into a complete, production-style academic web application named **Programming Contest Management System (PCMS)**.

## 1. Objective

Build the complete PCMS application using:

- HTML5
- CSS3
- Vanilla JavaScript (ES6+)
- PHP 8.2+
- Oracle Database, using OCI8 or PDO_OCI with named parameter binding
- Apache-compatible routing and configuration

The application must be fully database-driven, secure, responsive, and usable from beginning to end. It must implement all participant, organizer, and administrator workflows. Every visible button, form, link, table action, filter, pagination control, and navigation item must work. Do not leave placeholder pages, fake actions, hard-coded dashboard totals, dead buttons, or TODO-only features.

## 2. Read these sources before changing anything

Read and inspect the complete contents of:

1. `advance_data_base_report.pdf`
2. `ADBSMS Figma Design.pdf`
3. `tracker.md`

The PDF report describes the intended domain, Oracle schema, diagrams, seed data, and PL/SQL work. The Figma PDF is the visual reference for the seven existing screens. `tracker.md` contains the audit findings, missing requirements, defects, decisions, milestones, and acceptance checks.

Treat the existing material as a starting specification, not as automatically correct. Preserve the intended PCMS domain and visual identity while correcting the confirmed logical, data-model, consistency, accessibility, and security problems recorded in `tracker.md`.

## 3. Non-negotiable working rules

1. First inventory the repository and write a concise implementation plan mapped to the task IDs in `tracker.md`.
2. Implement the system in verifiable phases. Continue through safe, in-scope work without repeatedly asking for confirmation.
3. Do not claim completion until the application has been run, database objects compile, tests pass, and the main role-based workflows have been exercised through HTTP.
4. Use the product name **PCMS** everywhere. Remove the conflicting `CodeElite` and `CodeElite Systems` names.
5. Create one canonical 2026 demo dataset and use it consistently in Oracle seed data, UI pages, statistics, screenshots, and tests.
6. Do not copy inconsistent values from the mockups. Correct contradictions such as `CodeSprint 2025` paired with a 2026 date, different verdicts for the same submission, and `Two Sum` conflicting with the database’s `Sum of Two Numbers` fixture.
7. Follow the supplied visual design closely for colors, spacing, typography, cards, tables, badges, icons, forms, and layout. Extend the same design language to missing organizer/admin screens.
8. Logical usability and accessibility take priority when a mockup is incomplete or incorrect. Document any intentional visual deviation.
9. Do not execute untrusted participant source code directly with PHP `exec`, `shell_exec`, `system`, backticks, or on the web/database host.
10. Never store plaintext passwords. Never commit credentials, API keys, private invite codes, session secrets, or real user data.
11. Do not silently replace Oracle with MySQL or another database. If Oracle connectivity is unavailable, still create and validate the complete Oracle scripts as far as possible, document the exact blocker, and provide setup instructions.
12. Keep `tracker.md` updated: mark work in progress/completed only when its acceptance checks are satisfied, and append a dated progress-log entry.

## 4. Required architecture

Use a small, understandable MVC-style structure without introducing a heavy framework:

```text
/
|-- app/
|   |-- Controllers/
|   |-- Models/
|   |-- Repositories/
|   |-- Services/
|   |-- Middleware/
|   |-- Validation/
|   |-- Views/
|   `-- Support/
|-- config/
|-- database/
|   |-- 01_schema.sql
|   |-- 02_constraints_indexes.sql
|   |-- 03_seed.sql
|   |-- 04_program_units.sql
|   |-- 05_verify.sql
|   `-- reset.sql
|-- public/
|   |-- index.php
|   |-- .htaccess
|   `-- assets/
|       |-- css/
|       |-- js/
|       `-- images/
|-- routes/
|-- storage/
|   `-- logs/
|-- tests/
|-- .env.example
|-- composer.json
|-- README.md
|-- tracker.md
`-- implementation_prompt.md
```

Architecture requirements:

- Use `public/` as the web document root.
- Add a front controller and a clear route table.
- Keep SQL out of view templates.
- Put validation and business rules in reusable validation/service classes, not only JavaScript.
- Use repositories/models for database access and prepared statements with named binds.
- Use Composer PSR-4 autoloading. Composer may be used for autoloading and testing, but do not add a PHP framework unless explicitly approved.
- Render reusable PHP view partials for header, sidebar/top navigation, flash messages, pagination, cards, tables, modals, form fields, and footer.
- Use progressive enhancement: core forms must work server-side; JavaScript improves validation, filtering, modals, password visibility, countdowns, and user feedback.
- Add centralized exception handling and application logging. Do not expose stack traces or Oracle errors to users in production mode.

## 5. Corrected Oracle data model

Design the final schema before implementing UI actions. At minimum, support these entities or equivalent normalized structures:

- `APP_USER`
- `ROLE`
- `USER_ROLE`
- `CONTEST`
- `CONTEST_REGISTRATION`
- `PROBLEM`
- `TEST_CASE`
- `SUBMISSION`
- `ANNOUNCEMENT`
- `CLARIFICATION`
- `PASSWORD_RESET_TOKEN`
- `AUDIT_LOG`
- Optional `NOTIFICATION`
- If real judging is enabled: `LANGUAGE_RUNTIME`, `JUDGE_RUN`, and `SUBMISSION_TEST_RESULT`

Do not use Oracle-reserved identifiers. Use consistent uppercase snake_case SQL names and consistent PHP naming conventions.

### Required schema rules

- Use identity columns or create every required sequence consistently. Do not reference undeclared sequences.
- All relationships described as mandatory must be `NOT NULL`.
- Add foreign keys and indexes for every common relationship/access path.
- Add check constraints or lookup data for roles, contest visibility, approval status, registration status, problem difficulty, test-case visibility, submission verdict/state, and clarification status.
- Add `CREATED_AT` and `UPDATED_AT` consistently. Add domain-specific timestamps such as `REGISTERED_AT`, `SUBMITTED_AT`, `ANSWERED_AT`, and password-token expiry.
- Use `TIMESTAMP WITH TIME ZONE` for contest schedules and important event times, or enforce and document one platform time zone consistently.
- Enforce `END_TIME > START_TIME`, positive time/memory limits, non-negative execution metrics, and other numeric boundaries.
- Enforce `UNIQUE(CONTEST_ID, USER_ID)` on contest registrations.
- Enforce one leaderboard record per contest/user only if leaderboard snapshots are stored.
- Prefer calculating contest duration from start/end timestamps.
- Prefer calculating the leaderboard from accepted submissions through a view/query. If a cached leaderboard is used, update it atomically and provide a rebuild procedure.
- Store password hashes produced by PHP `password_hash`, never plaintext passwords.
- Use synthetic hashes in seed data and document demo login credentials separately in the README.
- Add answerer identity and asked/answered timestamps to clarifications.
- Add announcement author identity.
- Do not permit a user to organize a contest without the organizer/admin role.
- Define explicit restrict/archive/delete behavior so historical submissions and results cannot be accidentally destroyed.
- Make scripts runnable in a clean schema in deterministic order.
- Correct the existing smart-quote trigger defect and the Basic PL/SQL Q4 `UPCOMING`/`ONGOING` logic defect.

### Required PL/SQL deliverables

Preserve the academic PL/SQL requirements from the report, but place them in executable source files and correct them. Include and test:

- Stored functions
- Stored procedures
- `%ROWTYPE` table records
- Explicit cursors
- Cursor-based records
- Row-level triggers
- Statement-level triggers
- Packages and package bodies
- Sequences/identity behavior
- Positive and relevant negative test blocks

After installation, query Oracle object status and `USER_ERRORS`. The required result is zero invalid project objects and no compilation errors.

## 6. Authentication, authorization, and account behavior

Implement:

- Signup with server-side validation
- Login using email and `password_verify`
- Secure logout that destroys/regenerates the session appropriately
- “Remember me” only through a secure random selector/validator token design; never place a password in a cookie
- Forgot-password request and reset-token flow with hashed, expiring, single-use tokens
- Profile view/edit
- Password change with current-password verification
- Role-based access control for participant, organizer, and administrator routes
- CSRF protection on every state-changing request
- Session fixation protection and secure cookie settings
- Login throttling/rate limiting
- Generic authentication/reset messages that do not reveal whether an email exists
- Output escaping to prevent XSS
- Strict upload validation if profile images are supported: allow-listed MIME/type, random filename, size limit, storage outside executable paths, and a safe default avatar

Authorization must be checked server-side for every action. Hiding a button is not authorization.

## 7. Business rules

### Contest lifecycle

- Separate admin approval (`DRAFT`, `PENDING_APPROVAL`, `APPROVED`, `REJECTED`) from time-based lifecycle (`UPCOMING`, `ONGOING`, `COMPLETED`, `CANCELLED`).
- Derive time-based status from start/end timestamps where practical.
- Only approved, public contests are visible to the public.
- Private contests require an authorized registration/invite rule.
- Organizers can edit a contest only when rules allow it; critical changes after registration/submission require protection and audit logging.

### Registration

- Only authenticated participants may register.
- Prevent duplicate registration.
- Reject registration after the configured deadline/start time or for cancelled/rejected contests.
- Support registration status and cancellation rules.
- Derive the participant count from `CONTEST_REGISTRATION` rather than hard-coding it.

### Problems and test cases

- Organizers can create, read, update, archive/delete problems belonging to their own contests.
- Validate difficulty, time limit, memory limit, ordering/code (`A`, `B`, etc.), statement, input, output, constraints, sample input, and sample output.
- Test cases belong to one problem and can be public sample or hidden judge cases.
- Never expose hidden test-case input/output to participants.
- Participants can see problems only under the selected contest-visibility/start policy.

### Submissions and judging

- Only registered participants may submit to an eligible ongoing contest.
- Validate selected problem, supported language/runtime, source size, and submission window server-side.
- Initial submission state is `PENDING` or `QUEUED`.
- Create a `JudgeService` interface so judging is not mixed into controllers.
- For a database-focused academic MVP, an organizer/admin may process pending submissions through an authorized review flow, and the limitation must be clearly documented.
- If automatic judging is required, integrate an isolated judge service or sandbox through configuration. Never run submitted code directly on the PHP server.
- Support clear verdicts: `PENDING`, `RUNNING`, `ACCEPTED`, `WRONG_ANSWER`, `TIME_LIMIT_EXCEEDED`, `MEMORY_LIMIT_EXCEEDED`, `RUNTIME_ERROR`, `COMPILATION_ERROR`, and `SYSTEM_ERROR`.
- Store execution time and memory only when meaningful.
- Do not allow normal users to modify verdicts.

### Leaderboard

- Count a problem as solved once per participant after the first accepted submission.
- Define penalty consistently, including wrong attempts before acceptance.
- Sort by solved count descending, penalty ascending, then a documented tie-breaker.
- Display the top users plus the current user’s position, matching the Figma concept.
- Recalculate after relevant verdict changes.
- Do not use a user’s global rating as the contest rank.

### Clarifications and announcements

- Participants may ask contest-related questions during the permitted period.
- Validate length and contest registration/visibility.
- Organizers may view and answer clarifications only for contests they own; admins may moderate all.
- Track `OPEN`, `ANSWERED`, and `CLOSED` consistently.
- Participants may see their own questions and any clarifications explicitly marked public.
- Organizers can create/update/archive announcements for their contests; participants see announcements for visible/registered contests.

### Audit behavior

Audit sensitive actions such as role changes, contest approval, contest cancellation, problem/test changes after contest publication, verdict override, clarification moderation, and account status changes.

## 8. Required screens and CRUD coverage

### Public/authentication screens

- Login
- Signup
- Forgot password
- Reset password
- Public contest list
- Public contest detail where allowed
- Friendly 403, 404, 419/CSRF, and 500 pages

### Participant screens

- Dashboard matching the supplied design
- Contest list with search, filters, pagination, and status badges
- Contest detail with working Register/Cancel Registration and View Problems actions
- Problem list
- Problem statement with statement/input/output/constraints/limits/sample input/sample output
- Solution editor/submission form
- My submissions list with contest/problem/language/verdict/date filters and pagination
- Submission detail showing source, status, execution data, and judge message where authorized
- Contest leaderboard with pagination and current-user row
- Ask clarification form
- Clarification inbox/detail
- Announcements list/detail
- Notification area if implemented
- Profile and settings

### Organizer screens

- Organizer dashboard with owned-contest metrics
- Contest CRUD and submission-for-approval workflow
- Problem CRUD
- Test-case CRUD with safe hidden-case handling
- Announcement CRUD
- Clarification list, answer, close, and optional public/private response controls
- Submission monitoring and authorized judging/review action
- Contest participant list
- Contest leaderboard/report view

### Administrator screens

- Admin dashboard
- User list/search/filter/view/edit status and role assignments
- Contest approval/rejection with reason
- All-contest monitoring
- Submission/system monitoring
- Announcement/clarification moderation where required
- Audit-log viewer
- Read-only cross-contest reports and leaderboard access

## 9. Button and interaction contract

Audit every supplied Figma screen and create a button/action matrix. At minimum, implement and verify:

- Login, signup, forgot password, remember me, password visibility, and logout
- Sidebar/top navigation and active states
- Notification, profile/avatar, and settings controls
- View all contests, submissions, and announcements
- Register, cancel registration, and view problems
- Submit solution, open editor, reset source, language/problem selector, and contest filter
- Table row links, filters, pagination arrows, and empty-state actions
- Ask/cancel/submit clarification
- Organizer/admin create, edit, save, cancel, archive/delete, approve/reject, answer/close, and confirmation-dialog actions
- Footer links: either implement meaningful pages or remove them from the MVP; never leave `#` links

For each action, define:

- Who can see it
- Who can execute it
- Required validation
- Success result
- Failure result
- Redirect or updated state
- Audit requirement

## 10. UI implementation requirements

- Reproduce the provided Figma screens closely with reusable HTML/CSS components.
- Keep PCMS purple/lavender visual styling, rounded cards, summary cards, tables, badges, form controls, and clean whitespace.
- Use CSS custom properties for colors, typography, spacing, radius, shadows, layout widths, and status colors.
- Use one consistent navigation system. A responsive desktop sidebar may collapse to a mobile header/drawer, but page names and route availability must not change arbitrarily.
- Standardize terminology: use one label each for Dashboard, Contests, Problems, Submissions, Clarifications, Leaderboard, Announcements, Profile, and Settings.
- Use database values rather than hard-coded totals or rows.
- Use consistent verdict badges and full accessible labels; abbreviations may appear only when the meaning remains clear.
- Add loading, empty, success, error, pending, disabled, hover, focus, and permission-denied states.
- Make tables responsive with an intentional mobile pattern, not accidental page overflow.
- Ensure forms show field-level server validation errors and preserve safe user input after errors.
- Use semantic HTML, explicit labels, accessible names for icon buttons, visible keyboard focus, logical heading order, and keyboard-operable menus/modals.
- Target WCAG 2.2 AA contrast and interaction requirements.
- Provide desktop, tablet, and mobile layouts.
- Avoid inline styles and unnecessary duplicated CSS/JavaScript.
- Use only locally owned/licensed assets or clearly documented placeholders. Do not depend on unstable hotlinked images.

## 11. Routes and API behavior

Define a route inventory in the README. Use appropriate HTTP methods:

- `GET` for pages/read actions
- `POST` for create/login/register/commands
- Method override or explicit POST action for update/delete if the environment does not support `PUT/PATCH/DELETE`

Do not change state through `GET` links. Apply CSRF checks and authorization to every state-changing route. Return proper redirects and flash messages for traditional forms; return consistent JSON only for explicitly asynchronous endpoints.

## 12. Validation and security checklist

- Prepared/bound Oracle queries everywhere
- Server-side allow-list validation for all enum-like values
- CSRF protection
- Context-aware output escaping
- Secure password hashing and session management
- Role and ownership checks
- No IDOR: users cannot access another user’s private submission/clarification by changing an ID
- Pagination limits and bounded search inputs
- Transaction boundaries for multi-step writes
- Duplicate-request protection where needed
- Upload hardening if enabled
- Security headers appropriate for the app
- Production mode hides detailed exceptions
- No secrets in Git or browser JavaScript
- Audit logging for privileged changes
- Safe handling of CLOB source code and statements
- No direct execution of submitted code on the application host

## 13. Testing requirements

Create repeatable tests and a manual acceptance checklist.

### Database tests

- Clean installation
- Zero invalid Oracle objects
- FK, unique, check, required-field, schedule, and positive-number constraints
- Duplicate registration rejection
- Role/ownership rule enforcement
- PL/SQL positive/negative cases
- Leaderboard calculation, wrong-attempt penalties, ties, and zero-solve users
- Clarification answer/status consistency

### PHP tests

- Authentication success/failure and password hashing
- CSRF rejection
- Authorization for participant/organizer/admin routes
- Ownership/IDOR protection
- Form validation
- Contest registration boundaries
- Contest/problem/test/announcement/clarification CRUD
- Submission eligibility and verdict protection
- Leaderboard service behavior

### Browser acceptance tests

- Complete signup → login → register → view problem → submit → view result → leaderboard → clarification → logout flow
- Complete organizer contest/problem/test/announcement/clarification workflow
- Complete admin user/role/approval/audit workflow
- All buttons and links from the action matrix
- Empty/error/loading states
- Desktop/tablet/mobile layouts
- Keyboard navigation and visible focus

## 14. Required documentation

Create a complete `README.md` containing:

- Project overview and feature list
- Final architecture
- Requirements and tested versions
- Oracle setup and PHP OCI configuration
- Environment-variable setup using `.env.example`
- Exact database install/reset/verify order
- Exact local run instructions
- Demo accounts and roles using non-sensitive demo credentials
- Route/role matrix
- Judge-mode explanation and security limitation
- Test commands
- Known limitations
- Screenshots of final key pages

Also add:

- Database schema documentation
- Business-rule summary
- Button/action matrix
- Design deviation log
- Final verification report with commands/results

## 15. Definition of done

The project is complete only when all of the following are true:

- The repository contains actual runnable PHP, HTML, CSS, JavaScript, and Oracle SQL files.
- A clean Oracle schema installs with zero invalid objects.
- The application connects through environment configuration and loads through the documented web server route.
- Authentication, sessions, password handling, CSRF, RBAC, and ownership checks work.
- Participant, organizer, and administrator workflows work end to end.
- Every visible action has working behavior and server-side validation.
- All required CRUD operations persist to Oracle and return correct UI feedback.
- Dashboard counts, contest participants, submissions, announcements, clarifications, and leaderboard values come from the database.
- Figma-based screens are visually faithful, consistently branded as PCMS, responsive, and accessible.
- Known Figma/report inconsistencies are corrected and documented.
- Automated tests and the manual acceptance checklist pass.
- README setup instructions have been followed once from a clean state.
- `tracker.md` accurately reflects completed and remaining work.
- No placeholders, debug dumps, exposed secrets, dead routes, `href="#"` actions, or unverified “complete” claims remain.

## 16. Delivery sequence

Use this order:

1. Repository audit and final decisions
2. Corrected ERD/schema plan
3. Oracle DDL, constraints, indexes, seeds, PL/SQL, and verification
4. PHP foundation, router, database layer, sessions, CSRF, and RBAC
5. Authentication and account flows
6. Shared Figma-based design system/layout
7. Participant flows
8. Organizer CRUD and workflows
9. Administrator workflows
10. Leaderboard, clarification, announcement, and judging/review integration
11. Responsive/accessibility/security hardening
12. Automated and browser acceptance testing
13. Documentation, screenshots, and final tracker update

Start now by reading all three source artifacts, inventorying the repository, and producing the implementation plan. Then execute the plan phase by phase. Make reasonable in-scope decisions using the recommended defaults in `tracker.md`; stop only for a decision that would materially change the approved product or require unavailable credentials/infrastructure.

---

End of master prompt.
