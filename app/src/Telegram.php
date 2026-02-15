<?php

declare(strict_types=1);

namespace App\Src;

class Telegram
{
    public function __construct(private string $token)
    {
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        $this->request('sendMessage', $payload);
    }

    public function request(string $method, array $payload): array
    {
        $url = sprintf('https://api.telegram.org/bot%s/%s', $this->token, $method);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 15,
            ]
        ];
        $response = @file_get_contents($url, false, stream_context_create($opts));
        return $response ? (json_decode($response, true) ?: []) : [];
    }
}
