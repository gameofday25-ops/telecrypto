<?php

declare(strict_types=1);

namespace App\Src;

class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $file = dirname(__DIR__) . '/storage/logs/app.log';
        if (file_exists($file) && filesize($file) > 1024 * 1024 * 2) {
            @rename($file, dirname($file) . '/app-' . date('YmdHis') . '.log');
        }

        $line = sprintf("[%s] %s: %s %s\n", date('c'), strtoupper($level), $message, json_encode($context, JSON_UNESCAPED_UNICODE));
        file_put_contents($file, $line, FILE_APPEND);
    }
}
