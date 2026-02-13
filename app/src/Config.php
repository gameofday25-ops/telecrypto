<?php

declare(strict_types=1);

namespace App\Src;

class Config
{
    private static array $data = [];

    public static function load(string $basePath): void
    {
        if (self::$data) {
            return;
        }

        $envPath = $basePath . '/.env';
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            self::$data[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$data[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int)(self::get($key) ?? $default);
    }

    public static function adminIds(): array
    {
        $raw = self::get('ADMIN_IDS', '');
        if (!$raw) {
            return [];
        }

        return array_values(array_filter(array_map('intval', array_map('trim', explode(',', $raw)))));
    }
}
