<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';
\PCMS\Support\Env::load(dirname(__DIR__) . '/.env');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    \PCMS\Support\Database::scalar('SELECT 1');
    echo json_encode(['status' => 'ok']);
} catch (\Throwable) {
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
}
