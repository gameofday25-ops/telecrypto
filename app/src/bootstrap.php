<?php

declare(strict_types=1);

use App\Src\Config;

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Src\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

Config::load(dirname(__DIR__, 2));

date_default_timezone_set(Config::get('APP_TIMEZONE', 'UTC') ?: 'UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse(bool $ok, mixed $data = null, ?string $code = null, ?string $message = null, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($ok ? ['ok' => true, 'data' => $data] : ['ok' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    return json_decode($raw, true) ?: [];
}
