# Programming Contest Management System (PCMS)

PCMS is a role-based programming-contest web application built with PHP, Oracle, HTML, CSS, and vanilla JavaScript. It converts the supplied report and Figma export into an executable MVC-style project while correcting the model, security, branding, and workflow gaps in tracker.md.

## Implemented scope

- Participant signup, login, remember-me, logout, password reset token, profile, and password change
- Participant, organizer, and administrator role navigation and authorization
- Contest browser, registration/cancellation, problems, submissions, leaderboard, announcements, and clarifications
- Organizer contest/problem/test-case/announcement CRUD, clarification answering, participant list, and safe manual submission review
- Administrator user access, role assignment, contest approval/rejection, monitoring, and audit log
- Oracle schema, constraints, indexes, sequences, seed data, PL/SQL, views, reset, and verification scripts
- Responsive Figma-derived design with reusable tokens, cards, tables, badges, forms, focus states, mobile navigation, and empty/error states

## Architecture

public/index.php is the front controller. routes/web.php maps requests to controllers. Controllers validate requests and delegate Oracle work to repositories. Views contain presentation only. app/Support provides routing, OCI8 access, sessions, CSRF, validation, flash messages, and rendering.

The project uses no heavy framework. composer.json supplies PSR-4 metadata when Composer is available; app/autoload.php is the local fallback.

## Requirements

- PHP 8.2 or newer
- PHP OCI8 extension
- Oracle Database 19c, 21c, or 23ai recommended
- Oracle Instant Client compatible with the exact PHP build
- Apache with rewrite and headers, or PHP's development server

The development machine now uses PHP 8.5.5 with OCI8 3.4.1 and Oracle Instant Client 19.26. The connection has been verified against the local Oracle XE service and the `PCMS_APP` schema. A supported modern Oracle server remains strongly recommended for production.

## Configuration

1. Copy .env.example to .env.
2. Generate a long random APP_KEY.
3. Set the Oracle host, port, service, username, and password.
4. In production set APP_ENV=production, APP_DEBUG=false, SESSION_SECURE=true, and use HTTPS.
5. Never commit .env.

Check PHP:

    php --ini
    php -m
    php -r "var_dump(extension_loaded('oci8'));"

On Windows, create/load `php.ini`, install an OCI8 build matching the exact PHP version, architecture, thread-safety mode, and Visual C++ runtime, and put a compatible Oracle Instant Client on `Path`. Restart Apache and the terminal afterward. The tutorial-derived setup applied to this workstation is documented in `docs/oracle-php-connection.md`; `config/php.ini.example` contains the reproducible PHP settings.

## Oracle setup

Create a dedicated schema as a DBA. Never install PCMS into SYS or SYSTEM.

    CREATE USER PCMS_APP IDENTIFIED BY "<strong-random-password>";
    GRANT CREATE SESSION, CREATE TABLE, CREATE VIEW, CREATE SEQUENCE,
          CREATE PROCEDURE, CREATE TRIGGER TO PCMS_APP;
    ALTER USER PCMS_APP QUOTA UNLIMITED ON USERS;

Install:

    cd database
    sqlplus PCMS_APP@//127.0.0.1:1521/XE
    @install.sql

The verification output must show no invalid objects and no rows from USER_ERRORS.

Reset only the named PCMS objects after reviewing the script:

    @reset.sql

## Local server

After .env and OCI8 are ready:

    php -S 127.0.0.1:8765 -t public public/router.php

Open http://127.0.0.1:8765.

For Apache, point the virtual-host document root to public/, allow .htaccess overrides, and enable rewrite and headers.

## Demo accounts

All seeded accounts use the synthetic password Password123!.

| Role | Email |
|---|---|
| Administrator | admin@pcms.test |
| Organizer | organizer@pcms.test |
| Participant | participant@pcms.test |
| Participant | shishir@pcms.test |

Remove/change demo accounts before public deployment.

## Route and role summary

| Area | Participant | Organizer | Admin | Public |
|---|---:|---:|---:|---:|
| Approved contests, announcements, leaderboard | Yes | Yes | Yes | Yes |
| Contest registration | Yes | No | No | No |
| Problem view and submission | Yes | Preview | Monitor | No |
| Own submissions and questions | Yes | No | No | No |
| Contest/problem/test/announcement management | No | Own contests | Oversight | No |
| User access, approval, and audit | No | No | Yes | No |

All writes use POST with CSRF protection. Server-side role and ownership checks remain authoritative.

## Judging and password reset boundaries

The academic review mode stores participant submissions as PENDING and lets the owning organizer record a verdict. PHP never executes participant code. A real judge must be a separately isolated service with strict CPU, memory, wall-time, process, filesystem, and network controls.

Reset tokens are random, hashed in Oracle, one-hour, and single-use. Debug mode shows the reset URL so the flow can be tested without mail infrastructure. Production must add a real mail adapter and disable APP_DEBUG.

## Verification

    php tests/lint.php
    php tests/run.php
    php tests/oracle_connection.php

Then in SQL*Plus:

    @database/05_verify.sql

Complete the participant, organizer, and administrator journeys in docs/button-action-matrix.md.

With the development server running, execute the repeatable live role checks from a second PowerShell window:

    powershell -ExecutionPolicy Bypass -File tests/live_http.ps1

## Security summary

- password_hash and password_verify
- Named OCI binds
- CSRF on every write route
- Session ID rotation, HttpOnly, and SameSite cookies
- Selector/validator remember-me tokens
- Escaped view output
- Server-side role and ownership checks
- Hidden test-case protection
- Production exception hiding
- Audit records for privileged changes
- No direct execution of submitted code

Before deployment add HTTPS, persistent/shared login throttling, production email, backup/restore, and monitoring.

## Project references

- advance_data_base_report.pdf: original ADBMS report
- ADBSMS Figma Design.pdf: original visual reference
- implementation_prompt.md: implementation specification
- tracker.md: findings, decisions, backlog, and progress
- docs/button-action-matrix.md: action acceptance matrix
- docs/design-decisions.md: visual and product corrections

## Current environment status

OCI8 and Instant Client are configured and live PHP-to-Oracle HTTP workflows pass for participant, organizer, and administrator accounts. Production deployment still requires HTTPS, a supported Oracle release, a real mail adapter, shared login throttling, and an isolated judge service if automatic code execution is added.
