# PCMS deployment: Supabase → Render → Vercel

This repository was originally PHP + **Oracle OCI8**, not MySQL. The live application now uses PDO PostgreSQL. The original Oracle SQL remains for reference. Applying the new schema creates an **empty** PCMS database; it does not copy Oracle rows. Migrate real data separately if required, after checking IDs, password hashes, and foreign-key order.

## 1. Local run and database setup

Install PHP 8.2+ with `pdo_pgsql` enabled and PostgreSQL (or connect to Supabase). Check `php -m` for both `PDO` and `pdo_pgsql`. Copy `.env.example` to `.env` (never commit `.env`). For local PostgreSQL set `DB_HOST`, `DB_PORT=5432`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSLMODE=disable`, `APP_URL=http://127.0.0.1:8765`. Leave `DATABASE_URL` empty when using separate fields. Use a long random `APP_KEY`.

On a **new, empty** PostgreSQL database, run `database/postgres/01_schema.sql`, then `database/postgres/02_seed.sql`. Do not rerun the schema on a populated database. Test connection with `php tests/postgres_connection.php`. To create the first administrator, set `PCMS_ADMIN_NAME`, `PCMS_ADMIN_EMAIL`, and `PCMS_ADMIN_PASSWORD` (at least 12 characters) in your **local shell only**, then run `php database/postgres/bootstrap_admin.php`. The script refuses a second admin bootstrap. Remove the password environment variable afterward. Never put that password in Git, `.env.example`, or screenshots. Register additional participant accounts in `/register`; use `/admin/users` to assign the existing organizer role.

Run `php -S 127.0.0.1:8765 -t public public/router.php`; open `http://127.0.0.1:8765/`. `public/` is the only web root. No permanent file uploads are used, so Supabase Storage is not needed.

## 2. Supabase (manual dashboard work)

Create a Supabase PostgreSQL project. In **SQL Editor**, execute the complete `database/postgres/01_schema.sql`, then `database/postgres/02_seed.sql`. These create PCMS tables, indexes, views, functions, triggers, RLS, and role definitions. The PHP server connects as the database owner; browser clients must not use a Supabase key to query PCMS tables. Keep the Supabase Data API disabled for this project if you do not need it. Do not use a service-role key in the browser.

Copy the **Session pooler** PostgreSQL connection details from Supabase → Connect. Render often needs this IPv4-compatible endpoint rather than the direct IPv6 database host. Use either one `DATABASE_URL` (URL-encode reserved characters in the password) **or** the separate `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` values. For Supabase set `DB_SSLMODE=require`; if `DATABASE_URL` has `?sslmode=require`, that wins. Do not publish the database password.

Create the first admin with the local CLI bootstrap command above while your local `.env` points to Supabase. If your local PHP lacks `pdo_pgsql`, enable/install it first; the Render web service does not expose the bootstrap script. Confirm `SELECT role_code FROM role;` in SQL Editor. Existing Oracle data does not appear automatically.

## 3. Render (manual dashboard work)

Push this repository to GitHub, then create a Render **Web Service** from that repository/production branch. Choose **Docker**. `Dockerfile` installs `pdo_pgsql`, enables Apache rewrite/headers, and serves only `public/`; `docker/start.sh` binds Apache to Render's dynamic `$PORT`. No custom build/start command is required. Set health check path to `/health.php`.

Set environment variables: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://YOUR-VERCEL-DOMAIN` (for the first Render-only smoke check you can temporarily use the Render URL), `APP_KEY=<long random value>`, `SESSION_NAME=pcms_session`, `SESSION_SECURE=true`, `DB_SSLMODE=require`, and the Supabase `DATABASE_URL` **or** separate `DB_*` fields. `APP_TIMEZONE=Asia/Dhaka` is optional. Render supplies `PORT`; do not set a fixed port. Do **not** set `PCMS_ADMIN_PASSWORD` in the long-running web service. Enable automatic deploys from the production branch.

After deploy, visit `https://YOUR-RENDER-HOST.onrender.com/health.php`: it must return HTTP 200 JSON `{"status":"ok"}`. Check the direct Render homepage `/` once. If the health endpoint is 503, read Render logs and verify Supabase host/user/password, session-pooler port, project state, and SSL mode before proceeding.

## 4. Vercel startup/proxy (manual dashboard work)

After Render has a real URL, replace `https://REPLACE_WITH_RENDER_HOST.onrender.com` in **both** `vercel-proxy/vercel.json` and `vercel-proxy/index.html` with that exact HTTPS Render origin (no trailing slash), commit, and push. In Vercel, import the same GitHub repository, set **Root Directory** to `vercel-proxy`, choose the static/Other setup with no build command, and deploy. Enable automatic deployments from the production branch. Update Render `APP_URL` to the final Vercel HTTPS domain and redeploy Render if it was temporarily using the Render URL.

The Vercel root `/` remains a static startup page. It checks proxied `/health.php` and Render's actual homepage `/` via `/__render_home`; after **two consecutive rounds** in which both return HTTP 200, it opens `/contests` (the same PCMS contest homepage route). Failures, including 502 while Render wakes, retry every five seconds; the button retries immediately. All other Vercel paths proxy to Render. Check `/login`, CSS/JS assets, sign-in, and a role-protected page through Vercel. The startup page does not make Render truly always-on; Render Free sleeps when idle, while an always-on paid Render instance avoids that sleep.

## Troubleshooting

- **502 / waking up:** Open the direct Render `/health.php`. If it returns 200 but Vercel fails, verify the Render origin in both proxy files and redeploy Vercel. If it returns 503, fix the database connection in Render. If Render is waking, wait for the five-second retry.
- **Database / SSL:** Use the Supabase **Session pooler** host, its exact user/database/port, and `DB_SSLMODE=require`. Confirm `pdo_pgsql` exists in the Render image. With `DATABASE_URL`, URL-encode password characters such as `@`, `#`, and `/`.
- **Port / health:** Do not override Render's `$PORT`; Apache starts on that port. `/health.php` returns 503 unless `SELECT 1` succeeds, so a deployed container is not necessarily a ready app.
- **Sessions / redirects:** Set Render `APP_URL` to the final Vercel HTTPS origin and `SESSION_SECURE=true`. Use the same Vercel host for login and subsequent browsing; direct Render and Vercel are different cookie hosts. Render's local PHP session files can be lost on restart/redeploy, so users may need to sign in again. Multi-instance persistent sessions require a shared session store, not included here.
- **Missing assets / 404:** Confirm Render serves `public/`, Apache rewrite is enabled, exact Linux filename case matches imports, and Vercel's Root Directory is `vercel-proxy`. Verify the Render URL has no trailing slash in both proxy files.
- **Password reset:** The current project has no production email sender; reset links are shown only with `APP_DEBUG=true` locally. Keep production debug off and recover accounts through a private admin procedure until mail delivery is implemented.
