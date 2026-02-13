<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'status' => 'up']);
    exit;
}

if (str_starts_with($path, '/api/')) {
    require __DIR__ . '/api/index.php';
    exit;
}

if (str_starts_with($path, '/admin')) {
    require __DIR__ . '/admin/index.php';
    exit;
}

if ($path === '/bot.php') {
    require __DIR__ . '/bot.php';
    exit;
}

readfile(__DIR__ . '/app/index.html');
