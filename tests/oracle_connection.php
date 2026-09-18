<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use PCMS\Support\Database;

try {
    $version = Database::scalar('SELECT banner FROM v$version WHERE ROWNUM = 1');
    $users = Database::scalar('SELECT COUNT(*) FROM app_user');
    $contests = Database::scalar('SELECT COUNT(*) FROM contest');

    echo "OCI8 connection passed.\n";
    echo "Database: {$version}\n";
    echo "PCMS users: {$users}\n";
    echo "PCMS contests: {$contests}\n";
} catch (Throwable $error) {
    fwrite(STDERR, "OCI8 connection failed: {$error->getMessage()}\n");
    exit(1);
}

