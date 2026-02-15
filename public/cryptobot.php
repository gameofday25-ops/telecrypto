<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/src/bootstrap.php';

use App\Src\Config;
use App\Src\Repositories\AppRepository;

$secret = (string)Config::get('CRYPTOBOT_WEBHOOK_SECRET', '');
if ($secret !== '' && (($_GET['secret'] ?? '') !== $secret)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$updateType = (string)($payload['update_type'] ?? '');
$invoice = $payload['payload'] ?? null;
if (!is_array($invoice)) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($updateType === 'invoice_paid') {
    $invoiceId = (string)($invoice['invoice_id'] ?? '');
    if ($invoiceId !== '') {
        $repo = new AppRepository();
        $deposit = $repo->markDepositPaidByExternalId($invoiceId);
        if ($deposit) {
            $repo->audit('cryptobot', 'deposit.auto_paid', ['invoiceId' => $invoiceId, 'depositId' => $deposit['id']]);
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
