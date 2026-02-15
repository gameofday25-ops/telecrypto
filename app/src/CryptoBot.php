<?php

declare(strict_types=1);

namespace App\Src;

class CryptoBot
{
    private const API_BASE = 'https://pay.crypt.bot/api/';

    public function __construct(private string $apiToken)
    {
    }

    public function createInvoice(string $amountUsdt, string $payload): array
    {
        if ($this->apiToken === '') {
            throw new \RuntimeException('CryptoBot API token is not configured');
        }

        $body = [
            'asset' => 'USDT',
            'amount' => $amountUsdt,
            'description' => 'TeleCrypto deposit',
            'hidden_message' => 'Deposit will be credited after payment confirmation',
            'allow_comments' => false,
            'allow_anonymous' => false,
            'paid_btn_name' => 'openBot',
            'paid_btn_url' => (string)Config::get('WEBAPP_URL', ''),
            'payload' => $payload,
        ];

        $response = $this->request('createInvoice', $body);
        if (!($response['ok'] ?? false)) {
            throw new \RuntimeException('CryptoBot createInvoice failed');
        }

        return $response['result'] ?? [];
    }

    private function request(string $method, array $payload): array
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n" .
                    'Crypto-Pay-API-Token: ' . $this->apiToken . "\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 20,
            ],
        ];

        $raw = @file_get_contents(self::API_BASE . $method, false, stream_context_create($options));
        if (!$raw) {
            throw new \RuntimeException('CryptoBot request failed');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid CryptoBot response');
        }

        return $decoded;
    }
}
