<?php
declare(strict_types=1);

use PCMS\Support\Auth;
use PCMS\Support\Csrf;
use PCMS\Support\Env;

require __DIR__ . '/autoload.php';
Env::load(dirname(__DIR__) . '/.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Asia/Dhaka'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(Env::get('SESSION_NAME', 'pcms_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => Env::bool('SESSION_SECURE'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!Auth::check() && !empty($_COOKIE['pcms_remember']) && str_contains((string)$_COOKIE['pcms_remember'], ':')) {
    [$selector, $validator] = explode(':', (string)$_COOKIE['pcms_remember'], 2);
    try {
        $remembered = (new \PCMS\Repositories\UserRepository())->byRememberSelector($selector);
        if ($remembered && $remembered['account_status'] === 'ACTIVE' && password_verify($validator, $remembered['remember_validator_hash'])) {
            unset($remembered['remember_validator_hash']);
            Auth::login($remembered);
        }
    } catch (\Throwable) {
        // The central handler will show configuration errors when a database route is opened.
    }
}

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = '/'): string { return rtrim((string)Env::get('APP_URL', ''), '/') . '/' . ltrim($path, '/'); }
function redirect(string $path, int $status = 302): never { header('Location: ' . url($path), true, $status); exit; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">'; }
function user(): ?array { return Auth::user(); }
function has_role(string $role): bool { return Auth::hasRole($role); }
function input(string $key, mixed $default = ''): mixed { return $_POST[$key] ?? $default; }
