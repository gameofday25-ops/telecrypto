<?php

declare(strict_types=1);

namespace App\Src\Repositories;

use App\Src\Db;

class AppRepository
{
    public function upsertUser(array $tgUser): array
    {
        $db = Db::conn();
        $stmt = $db->prepare('SELECT * FROM users WHERE telegram_id = ?');
        $stmt->execute([$tgUser['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            return $existing;
        }

        $ins = $db->prepare('INSERT INTO users (telegram_id, username, first_name, last_name) VALUES (?, ?, ?, ?)');
        $ins->execute([
            $tgUser['id'],
            $tgUser['username'] ?? null,
            $tgUser['first_name'] ?? null,
            $tgUser['last_name'] ?? null,
        ]);

        $userId = (int)$db->lastInsertId();
        $db->prepare('INSERT INTO balances (user_id, available, reserved) VALUES (?, 0, 0)')->execute([$userId]);
        return $this->getUserById($userId);
    }

    public function getUserByTelegramId(int $telegramId): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE telegram_id = ?');
        $stmt->execute([$telegramId]);
        return $stmt->fetch() ?: null;
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function getBalance(int $userId): array
    {
        $stmt = Db::conn()->prepare('SELECT available, reserved FROM balances WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: ['available' => '0', 'reserved' => '0'];
    }

    public function listTokens(string $search = '', bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM tokens WHERE 1=1';
        $params = [];
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR symbol LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getToken(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM tokens WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function listCollections(): array
    {
        $rows = Db::conn()->query('SELECT * FROM collections WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll();
        foreach ($rows as &$row) {
            $stmt = Db::conn()->prepare('SELECT ci.sort, t.* FROM collection_items ci JOIN tokens t ON t.id = ci.token_id WHERE ci.collection_id = ? ORDER BY ci.sort ASC');
            $stmt->execute([$row['id']]);
            $row['items'] = $stmt->fetchAll();
        }
        return $rows;
    }

    public function toggleFavorite(int $userId, int $tokenId): bool
    {
        $db = Db::conn();
        $stmt = $db->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND token_id = ?');
        $stmt->execute([$userId, $tokenId]);
        if ($stmt->fetch()) {
            $db->prepare('DELETE FROM favorites WHERE user_id = ? AND token_id = ?')->execute([$userId, $tokenId]);
            return false;
        }
        $db->prepare('INSERT INTO favorites (user_id, token_id) VALUES (?, ?)')->execute([$userId, $tokenId]);
        return true;
    }

    public function createDeposit(int $userId, string $amount, string $method): int
    {
        $stmt = Db::conn()->prepare('INSERT INTO deposits (user_id, amount_usdt, method, status) VALUES (?, ?, ?, "PENDING")');
        $stmt->execute([$userId, $amount, $method]);
        return (int)Db::conn()->lastInsertId();
    }

    public function listDeposits(int $userId): array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function listOrders(int $userId): array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $it = Db::conn()->prepare('SELECT oi.*, t.name, t.symbol FROM order_items oi JOIN tokens t ON t.id = oi.token_id WHERE oi.order_id = ?');
            $it->execute([$order['id']]);
            $order['items'] = $it->fetchAll();
        }
        return $orders;
    }

    public function createOrder(int $userId, array $items): int
    {
        $db = Db::conn();
        $db->beginTransaction();
        try {
            $total = '0';
            foreach ($items as $item) {
                $total = bcadd($total, (string)$item['amountUsdt'], 8);
            }

            $balance = $this->getBalance($userId);
            if (bccomp((string)$balance['available'], $total, 8) < 0) {
                throw new \RuntimeException('Insufficient balance');
            }

            $db->prepare('INSERT INTO orders (user_id, status, total_amount_usdt) VALUES (?, "NEW", ?)')->execute([$userId, $total]);
            $orderId = (int)$db->lastInsertId();

            $stmt = $db->prepare('INSERT INTO order_items (order_id, token_id, amount_usdt, price_ref) VALUES (?, ?, ?, 0)');
            foreach ($items as $item) {
                $stmt->execute([$orderId, $item['tokenId'], $item['amountUsdt']]);
            }

            $db->prepare('UPDATE balances SET available = available - ?, reserved = reserved + ? WHERE user_id = ?')->execute([$total, $total, $userId]);
            $db->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function audit(string $actor, string $action, array $payload = []): void
    {
        $stmt = Db::conn()->prepare('INSERT INTO audit_log (actor, action, payload_json) VALUES (?, ?, ?)');
        $stmt->execute([$actor, $action, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }
}
