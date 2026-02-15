<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/src/bootstrap.php';

use App\Src\Config;
use App\Src\Repositories\AppRepository;
use App\Src\Telegram;

$token = (string)Config::get('BOT_TOKEN');
$webAppUrl = (string)Config::get('WEBAPP_URL', '');
$repo = new AppRepository();
$telegram = new Telegram($token);

$update = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$message = $update['message'] ?? null;
if (!$message) {
    echo 'ok';
    exit;
}

$text = trim((string)($message['text'] ?? ''));
$from = $message['from'] ?? [];
$chatId = (int)($message['chat']['id'] ?? 0);

if (!$chatId || empty($from['id'])) {
    echo 'ok';
    exit;
}

switch (true) {
    case str_starts_with($text, '/start'):
        $repo->upsertUser($from);
        $telegram->sendMessage($chatId, "Добро пожаловать! Откройте WebApp для работы.", [
            'inline_keyboard' => [[['text' => 'Открыть приложение', 'web_app' => ['url' => $webAppUrl]]]]
        ]);
        break;
    case str_starts_with($text, '/help'):
        $telegram->sendMessage($chatId, "/start\n/help\n/orders\n/wallet\n/support");
        break;
    case str_starts_with($text, '/orders'):
        $user = $repo->getUserByTelegramId((int)$from['id']);
        $orders = $user ? $repo->listOrders((int)$user['id']) : [];
        $telegram->sendMessage($chatId, 'Ваши заявки: ' . count($orders));
        break;
    case str_starts_with($text, '/wallet'):
        $user = $repo->getUserByTelegramId((int)$from['id']);
        $bal = $user ? $repo->getBalance((int)$user['id']) : ['available' => '0', 'reserved' => '0'];
        $telegram->sendMessage($chatId, "Баланс: available {$bal['available']} USDT, reserved {$bal['reserved']} USDT");
        break;
    case str_starts_with($text, '/support'):
        $telegram->sendMessage($chatId, 'Запрос в поддержку принят.');
        foreach (Config::adminIds() as $adminId) {
            $telegram->sendMessage($adminId, "Support from @" . ($from['username'] ?? $from['id']) . ': user requested help');
        }
        break;
    default:
        $telegram->sendMessage($chatId, 'Неизвестная команда. Используйте /help');
}

echo 'ok';
