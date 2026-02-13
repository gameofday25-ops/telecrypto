<?php

declare(strict_types=1);

namespace App\Src;

class Security
{
    public static function validateInitData(string $initData, string $botToken): array
    {
        parse_str($initData, $data);
        $hash = $data['hash'] ?? '';
        unset($data['hash']);

        ksort($data);
        $checkString = [];
        foreach ($data as $key => $value) {
            $checkString[] = $key . '=' . $value;
        }

        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', implode("\n", $checkString), $secret);

        if (!hash_equals($calculatedHash, $hash)) {
            throw new \RuntimeException('Invalid initData signature');
        }

        $authDate = (int)($data['auth_date'] ?? 0);
        if (time() - $authDate > 86400) {
            throw new \RuntimeException('initData expired');
        }

        $user = json_decode($data['user'] ?? '{}', true) ?: [];
        if (empty($user['id'])) {
            throw new \RuntimeException('User not found in initData');
        }

        return $user;
    }
}
