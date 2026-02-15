<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/src/bootstrap.php';

use App\Src\Admin\AdminAuth;
use App\Src\Admin\AdminService;
use App\Src\Config;
use App\Src\CryptoBot;
use App\Src\RateLimiter;
use App\Src\Repositories\AppRepository;
use App\Src\Security;
use App\Src\Services\AppService;
use App\Src\Telegram;
use App\Src\Providers\DbTokenProvider;

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = preg_replace('#/api/index\.php$#', '', $scriptName) ?: '';
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base)) ?: '';
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!RateLimiter::check($ip, $path)) {
        jsonResponse(false, null, 'RATE_LIMIT', 'Too many requests', 429);
    }

    $repo = new AppRepository();
    $service = new AppService($repo, new DbTokenProvider($repo));
    $adminService = new AdminService($repo, new Telegram((string)Config::get('BOT_TOKEN')));
    $input = getJsonInput();

    $needsAuth = !str_contains($path, '/api/admin/');
    $userTelegramId = null;
    if ($needsAuth) {
        $initData = $_SERVER['HTTP_X_TELEGRAM_INITDATA'] ?? ($_GET['initData'] ?? '');
        if (!$initData) {
            jsonResponse(false, null, 'UNAUTHORIZED', 'Missing initData', 401);
        }

        try {
            $user = Security::validateInitData($initData, (string)Config::get('BOT_TOKEN'));
            $userTelegramId = (int)$user['id'];
            $repo->upsertUser($user);
        } catch (Throwable $e) {
            jsonResponse(false, null, 'UNAUTHORIZED', $e->getMessage(), 401);
        }
    }

    if ($method === 'GET' && $path === '/api/me') {
        jsonResponse(true, $service->getMe($userTelegramId));
    }
    if ($method === 'GET' && $path === '/api/tokens') {
        jsonResponse(true, $service->tokens((string)($_GET['search'] ?? ''), ($_GET['active'] ?? '1') === '1'));
    }
    if ($method === 'GET' && preg_match('#^/api/tokens/(\d+)$#', $path, $m)) {
        jsonResponse(true, $service->token((int)$m[1]));
    }
    if ($method === 'GET' && $path === '/api/collections') {
        jsonResponse(true, $service->collections());
    }
    if ($method === 'POST' && $path === '/api/favorites/toggle') {
        jsonResponse(true, ['favorite' => $service->toggleFavorite($userTelegramId, (int)($input['tokenId'] ?? 0))]);
    }
    if ($method === 'POST' && $path === '/api/orders/create') {
        jsonResponse(true, ['orderId' => $service->createOrder($userTelegramId, $input['items'] ?? [])]);
    }
    if ($method === 'GET' && $path === '/api/orders/list') {
        jsonResponse(true, $service->listOrders($userTelegramId));
    }
    if ($method === 'GET' && $path === '/api/deposits/list') {
        jsonResponse(true, $service->listDeposits($userTelegramId));
    }
    if ($method === 'POST' && $path === '/api/deposits/create') {
        $amount = (string)($input['amountUsdt'] ?? '0');
        $methodName = strtoupper((string)($input['method'] ?? ''));

        if ($methodName === 'CRYPTOBOT') {
            $payload = 'telecrypto_' . $userTelegramId . '_' . bin2hex(random_bytes(6));
            $invoice = (new CryptoBot((string)Config::get('CRYPTOBOT_API_TOKEN', '')))->createInvoice($amount, $payload);
            $invoiceId = (string)($invoice['invoice_id'] ?? '');
            $payUrl = (string)($invoice['pay_url'] ?? '');
            if ($invoiceId === '' || $payUrl === '') {
                throw new RuntimeException('CryptoBot invoice is invalid');
            }

            $depositId = $service->createDeposit($userTelegramId, $amount, $methodName, $invoiceId, $payUrl);
            $repo->audit((string)$userTelegramId, 'deposit.create.cryptobot', ['depositId' => $depositId, 'invoiceId' => $invoiceId]);
            jsonResponse(true, ['depositId' => $depositId, 'payUrl' => $payUrl, 'invoiceId' => $invoiceId]);
        }

        $depositId = $service->createDeposit($userTelegramId, $amount, $methodName);
        $repo->audit((string)$userTelegramId, 'deposit.create', ['depositId' => $depositId, 'method' => $methodName]);
        jsonResponse(true, ['depositId' => $depositId]);
    }

    if (str_starts_with($path, '/api/admin/')) {
        if (!AdminAuth::checkSession()) {
            $initData = $_SERVER['HTTP_X_TELEGRAM_INITDATA'] ?? ($input['initData'] ?? '');
            if (!$initData) {
                jsonResponse(false, null, 'UNAUTHORIZED', 'Admin auth required', 401);
            }
            AdminAuth::requireAdmin($initData);
        }

        if ($method === 'POST' && in_array($path, ['/api/admin/token/create', '/api/admin/token/update'], true)) {
            $adminService->createOrUpdateToken($input);
            jsonResponse(true, ['saved' => true]);
        }
        if ($method === 'POST' && in_array($path, ['/api/admin/collection/create', '/api/admin/collection/update'], true)) {
            $adminService->createOrUpdateCollection($input);
            jsonResponse(true, ['saved' => true]);
        }
        if ($method === 'POST' && $path === '/api/admin/order/status') {
            $adminService->updateOrderStatus((int)($input['orderId'] ?? 0), (string)($input['status'] ?? ''));
            jsonResponse(true, ['saved' => true]);
        }
        if ($method === 'POST' && $path === '/api/admin/deposit/approve') {
            $adminService->approveDeposit((int)($input['depositId'] ?? 0));
            jsonResponse(true, ['saved' => true]);
        }
    }

    jsonResponse(false, null, 'NOT_FOUND', 'Endpoint not found', 404);
} catch (Throwable $e) {
    jsonResponse(false, null, 'SERVER_ERROR', $e->getMessage(), 500);
}
