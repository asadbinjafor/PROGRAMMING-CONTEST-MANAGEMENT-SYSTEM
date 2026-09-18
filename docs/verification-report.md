# PCMS Verification Report

Date: 2026-09-18

## PHP and static checks

- PHP version: 8.5.5 CLI
- PHP files linted: 63
- Result: pass
- Route/security/static smoke assertions: 68
- Result: pass
- Login route over PHP development server: HTTP 200
- CSS asset route: HTTP 200
- Login page contains a CSRF token: pass
- Desktop login visual review: pass
- Mobile viewport tested at 390 x 844: no horizontal overflow
- Stale CodeElite branding and hash-only links: none found by smoke check
- OCI8 extension: 3.4.1, enabled
- Oracle run-time client: 19.26.0.0.0
- Live PHP-to-Oracle connection test: pass
- Public database-driven HTTP pages: 4/4 pass
- Participant, organizer, and administrator dashboards: 3/3 pass
- Representative role-specific pages: 3/3 pass
- Representative forbidden role boundaries: 3/3 return HTTP 403
- Authenticated participant dashboard visual review: pass

Commands:

    php tests/lint.php
    php tests/run.php
    php tests/oracle_connection.php
    php -S 127.0.0.1:8765 -t public public/router.php
    powershell -ExecutionPolicy Bypass -File tests/live_http.ps1

## Oracle checks

- Local server: Oracle Database 10g Express Edition 10.2.0.1.0
- Dedicated schema: PCMS_APP
- Clean reset and reinstall: pass
- Tables created: 12
- Sequences created: 10
- Views created: 2
- Procedures created: 4
- Functions created: 2
- Row/statement triggers created: 4
- Package specifications/bodies created: 2 each
- Invalid project objects: 0
- USER_ERRORS rows: 0
- Seed counts: 4 users, 1 contest, 2 registrations, 3 problems, 2 submissions
- Derived contest duration: 240 minutes
- Derived participant count: 2
- Leaderboard query: pass
- Function/package positive calls: pass

Negative tests passed:

1. Invalid account status rejected
2. End time equal to start time rejected
3. Invalid submission verdict rejected
4. Inconsistent clarification answer rejected
5. Duplicate active registration rejected

## PHP to Oracle integration

The connection follows `DatabaseConnectionTutorial.pptx`: OCI8 is enabled in the active PHP configuration, Oracle Instant Client 19.26 is available to PHP, and PCMS uses one shared `oci_connect()` adapter. Credentials remain in the ignored `.env` file and every repository statement uses named binds.

The first live HTTP run found and resolved two integration defects:

1. Oracle 10g rejected the pagination bind name `:end` as a reserved word. Pagination now uses `:row_start` and `:row_end`.
2. The view renderer's internal `$data` parameter shadowed dashboard data. The renderer now extracts from `$viewData` so role metrics render correctly.

Remaining deployment-level checks:

- Full mutation-path HTTP tests with rollback fixtures
- Automated WCAG 2.2 AA scan of every authenticated page
- Production password-reset email delivery
- HTTPS and shared login throttling
- Upgrade from the unsupported Oracle XE 10g server before production use
