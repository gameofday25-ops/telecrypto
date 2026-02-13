<?php

declare(strict_types=1);

namespace App\Src;

class RateLimiter
{
    public static function check(string $ip, string $endpoint, int $limit = 120, int $window = 60): bool
    {
        $db = Db::conn();
        $stmt = $db->prepare('SELECT id, hits, window_start FROM rate_limits WHERE ip = ? AND endpoint = ?');
        $stmt->execute([$ip, $endpoint]);
        $row = $stmt->fetch();
        $now = time();

        if (!$row) {
            $insert = $db->prepare('INSERT INTO rate_limits (ip, endpoint, hits, window_start) VALUES (?, ?, 1, ?)');
            $insert->execute([$ip, $endpoint, $now]);
            return true;
        }

        if ($now - (int)$row['window_start'] > $window) {
            $upd = $db->prepare('UPDATE rate_limits SET hits = 1, window_start = ? WHERE id = ?');
            $upd->execute([$now, $row['id']]);
            return true;
        }

        if ((int)$row['hits'] >= $limit) {
            return false;
        }

        $upd = $db->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE id = ?');
        $upd->execute([$row['id']]);
        return true;
    }
}
