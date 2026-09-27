<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use PCMS\Support\Database;

try {
    $version = Database::scalar('SELECT version()');
    $users = Database::scalar('SELECT COUNT(*) FROM app_user');
    $contests = Database::scalar('SELECT COUNT(*) FROM contest');

    echo "PostgreSQL connection passed.\n";
    echo "Database: {$version}\n";
    echo "PCMS users: {$users}\n";
    echo "PCMS contests: {$contests}\n";
} catch (Throwable $error) {
    fwrite(STDERR, "PostgreSQL connection failed: {$error->getMessage()}\n");
    exit(1);
}

