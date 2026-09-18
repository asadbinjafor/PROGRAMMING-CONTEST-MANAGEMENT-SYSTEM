<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use PCMS\Support\Env;
use PCMS\Support\Router;

if (Env::get('APP_ENV', 'production') === 'production') ini_set('display_errors', '0');
$routes = require dirname(__DIR__) . '/routes/web.php';

try {
    (new Router($routes))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $error) {
    error_log($error->__toString());
    http_response_code(500);
    \PCMS\Support\View::render('errors/status', [
        'title' => 'Application unavailable',
        'code' => 500,
        'message' => Env::bool('APP_DEBUG') ? $error->getMessage() : 'An unexpected error occurred. Please try again later.',
    ]);
}

