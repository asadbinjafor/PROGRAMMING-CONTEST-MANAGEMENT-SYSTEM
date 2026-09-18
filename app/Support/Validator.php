<?php
declare(strict_types=1);

namespace PCMS\Support;

final class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label): self
    {
        if ($value === null || trim((string)$value) === '') $this->errors[$field] = "{$label} is required.";
        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) $this->errors[$field] = 'Enter a valid email address.';
        return $this;
    }

    public function length(string $field, mixed $value, int $min, int $max, string $label): self
    {
        $text = trim((string)$value);
        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($length < $min || $length > $max) $this->errors[$field] = "{$label} must be between {$min} and {$max} characters.";
        return $this;
    }

    public function in(string $field, mixed $value, array $allowed, string $label): self
    {
        if (!in_array($value, $allowed, true)) $this->errors[$field] = "Select a valid {$label}.";
        return $this;
    }

    public function errors(): array { return $this->errors; }
    public function fails(): bool { return $this->errors !== []; }
}
