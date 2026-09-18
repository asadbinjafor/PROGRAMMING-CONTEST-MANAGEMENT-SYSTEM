<?php
declare(strict_types=1);

namespace PCMS\Support;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && hash_equals(self::token(), $token);
    }

    public static function rotate(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}

