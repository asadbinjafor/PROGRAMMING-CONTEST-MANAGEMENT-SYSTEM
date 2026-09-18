# PHP to Oracle connection

This setup follows the workflow demonstrated in `DatabaseConnectionTutorial.pptx`, including its embedded 23-minute video, while retaining PCMS's existing MVC architecture and named-bind database adapter.

## Tutorial pattern used

1. Enable OCI8 in the active `php.ini`.
2. Install Oracle Instant Client 19.26.
3. Add the Instant Client directory to the Windows user `Path`.
4. Make the client DLLs visible to the PHP or Apache process.
5. Connect with `oci_connect()` and verify OCI8 through PHP before opening database-driven pages.

The tutorial's example puts the connection function in `model/dbconn.php` and calls it from model functions. PCMS already centralizes that responsibility in `app/Support/Database.php`, so duplicating credentials in each model would weaken the application. PCMS repositories call the shared adapter instead.

## Applied workstation configuration

- PHP: 8.5.5, x64, non-thread-safe, Visual C++ 2022
- OCI8: 3.4.1, `php_oci8_19.dll`
- Oracle Instant Client: 19.26 at `C:\Oracle\instantclient_19_26`
- Active PHP configuration: `C:\php\php.ini`
- Oracle service: local XE
- Application schema: `PCMS_APP`

The OCI8 DLL is installed in `C:\php\ext`. The tutorial's three runtime files (`oci.dll`, `oraociei19.dll`, and `oraons.dll`) are available beside `php.exe`, while the full Instant Client directory is placed first in the user's `Path`.

Use `config/php.ini.example` when reproducing the PHP configuration on another machine. Never put the database password in `php.ini` or source code. PCMS loads it from the ignored `.env` file.

## Verification

Run:

    php --ini
    php --ri oci8
    php tests/oracle_connection.php

Expected key values:

    OCI8 Support => enabled
    OCI8 Version => 3.4.1
    Oracle Run-time Client Library Version => 19.26.0.0.0
    OCI8 connection passed.

Start the app and run the role checks:

    php -S 127.0.0.1:8765 -t public public/router.php
    powershell -ExecutionPolicy Bypass -File tests/live_http.ps1

The test covers public database pages, all three role dashboards, role-specific pages, and representative 403 authorization boundaries.

## Apache or XAMPP note

The tutorial uses XAMPP and copies the Oracle client files into `C:\xampp\apache\bin`. This workstation does not currently contain a full XAMPP/Apache installation. When deploying under XAMPP, use a PHP build and OCI8 DLL with matching version, architecture, thread-safety mode, and compiler; then restart Apache and confirm OCI8 in `phpinfo()`.

