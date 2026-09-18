<?php
declare(strict_types=1);

namespace PCMS\Support;

final class Auth
{
    public static function user(): ?array { return $_SESSION['auth_user'] ?? null; }
    public static function id(): ?int { return isset($_SESSION['auth_user']['user_id']) ? (int)$_SESSION['auth_user']['user_id'] : null; }
    public static function check(): bool { return self::id() !== null; }
    public static function hasRole(string $role): bool { return in_array(strtoupper($role), self::user()['roles'] ?? [], true); }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['auth_user'] = $user;
        Csrf::rotate();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

