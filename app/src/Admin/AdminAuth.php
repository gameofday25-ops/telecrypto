<?php

declare(strict_types=1);

namespace App\Src\Admin;

use App\Src\Config;
use App\Src\Security;

class AdminAuth
{
    public static function requireAdmin(string $initData): array
    {
        $user = Security::validateInitData($initData, (string)Config::get('BOT_TOKEN'));
        if (!in_array((int)$user['id'], Config::adminIds(), true)) {
            throw new \RuntimeException('Admin only');
        }
        $_SESSION['admin_telegram_id'] = (int)$user['id'];
        return $user;
    }

    public static function checkSession(): bool
    {
        return isset($_SESSION['admin_telegram_id']) && in_array((int)$_SESSION['admin_telegram_id'], Config::adminIds(), true);
    }
}
