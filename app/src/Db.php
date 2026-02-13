<?php

declare(strict_types=1);

namespace App\Src;

use PDO;

class Db
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306'),
            Config::get('DB_NAME', '')
        );

        self::$pdo = new PDO($dsn, Config::get('DB_USER', ''), Config::get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
