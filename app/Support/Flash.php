<?php
declare(strict_types=1);

namespace PCMS\Support;

final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash'][] = compact('type', 'message');
    }

    public static function consume(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}

