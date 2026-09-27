# Programming Contest Management System (PCMS)

PCMS is a PHP 8.2+ academic contest application with HTML, CSS, vanilla JavaScript, and PostgreSQL. Its existing participant, organizer, and administrator workflows and UI are preserved. The production topology is Render (PHP application), Supabase (PostgreSQL), Vercel (static startup page and reverse proxy), and GitHub (source and automatic deployments).

Start with [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md). The web document root is `public/`; `public/index.php` is the front controller and `routes/web.php` is the route table. All application SQL is in `app/Repositories/` and uses named PDO parameters. PostgreSQL schema and role seed are in `database/postgres/`.

Local quick start after PostgreSQL and `pdo_pgsql` are installed:

1. Copy `.env.example` to `.env` and set local database credentials and a random `APP_KEY`.
2. Apply `database/postgres/01_schema.sql` and then `database/postgres/02_seed.sql` to a **new** database.
3. Run `php database/postgres/bootstrap_admin.php` once with `PCMS_ADMIN_NAME`, `PCMS_ADMIN_EMAIL`, and `PCMS_ADMIN_PASSWORD` set in the environment.
4. Run `php -S 127.0.0.1:8765 -t public public/router.php` and open `http://127.0.0.1:8765/`.

The PostgreSQL seed creates roles only; it contains no demo accounts or passwords. A newly registered user is a participant. An administrator can assign the organizer role through the existing admin UI. Automatic source-code judging is **not** included: submissions remain in the existing manual-review workflow. Production password-reset email delivery is also not configured; keep `APP_DEBUG=false` and provide a private account-recovery process until a mail adapter is added.

The old `database/*.sql` Oracle scripts and `docs/oracle-php-connection.md` are historical references, not the production database setup. Existing Oracle rows are **not** automatically migrated by the new PostgreSQL schema. Do not run the old Oracle installer against Supabase.

Checks: `php tests/lint.php`, `php tests/run.php`, and (after configuring PostgreSQL) `php tests/postgres_connection.php`. The health endpoint is `GET /health.php` and returns HTTP 200 only when a database query succeeds.
