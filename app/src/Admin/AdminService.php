<?php

declare(strict_types=1);

namespace App\Src\Admin;

use App\Src\Db;
use App\Src\Repositories\AppRepository;
use App\Src\Telegram;

class AdminService
{
    public function __construct(private AppRepository $repo, private Telegram $telegram)
    {
    }

    public function createOrUpdateToken(array $data): void
    {
        $db = Db::conn();
        if (!empty($data['id'])) {
            $stmt = $db->prepare('UPDATE tokens SET name=?, symbol=?, chain=?, expected_listing_at=?, risk_level=?, description=?, source=?, is_active=? WHERE id=?');
            $stmt->execute([$data['name'],$data['symbol'],$data['chain'],$data['expected_listing_at'] ?: null,$data['risk_level'],$data['description'],$data['source'] ?: 'manual',(int)$data['is_active'],(int)$data['id']]);
            $this->repo->audit('admin', 'token.update', $data);
            return;
        }
        $stmt = $db->prepare('INSERT INTO tokens (name,symbol,chain,expected_listing_at,risk_level,description,source,is_active) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$data['name'],$data['symbol'],$data['chain'],$data['expected_listing_at'] ?: null,$data['risk_level'],$data['description'],$data['source'] ?: 'manual',(int)$data['is_active']]);
        $this->repo->audit('admin', 'token.create', $data);
    }

    public function createOrUpdateCollection(array $data): void
    {
        $db = Db::conn();
        $collectionId = (int)($data['id'] ?? 0);
        if ($collectionId > 0) {
            $db->prepare('UPDATE collections SET title=?, description=?, is_active=? WHERE id=?')->execute([$data['title'],$data['description'],(int)$data['is_active'],$collectionId]);
        } else {
            $db->prepare('INSERT INTO collections (title,description,is_active) VALUES (?,?,?)')->execute([$data['title'],$data['description'],(int)$data['is_active']]);
            $collectionId = (int)$db->lastInsertId();
        }

        $db->prepare('DELETE FROM collection_items WHERE collection_id=?')->execute([$collectionId]);
        $ins = $db->prepare('INSERT INTO collection_items (collection_id, token_id, sort) VALUES (?, ?, ?)');
        foreach (($data['items'] ?? []) as $i => $tokenId) {
            $ins->execute([$collectionId, (int)$tokenId, $i + 1]);
        }

        $this->repo->audit('admin', 'collection.save', ['id' => $collectionId]);
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        $db = Db::conn();
        $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $orderId]);

        $orderStmt = $db->prepare('SELECT o.id, o.user_id, u.telegram_id FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?');
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();
        if ($order) {
            $this->telegram->sendMessage((int)$order['telegram_id'], "Статус заявки #{$orderId}: <b>{$status}</b>");
        }
        $this->repo->audit('admin', 'order.status', ['orderId' => $orderId, 'status' => $status]);
    }

    public function approveDeposit(int $depositId): void
    {
        $db = Db::conn();
        $stmt = $db->prepare('SELECT d.*, u.telegram_id FROM deposits d JOIN users u ON u.id = d.user_id WHERE d.id = ?');
        $stmt->execute([$depositId]);
        $dep = $stmt->fetch();
        if (!$dep) {
            throw new \RuntimeException('Deposit not found');
        }
        if ($dep['status'] !== 'PENDING') {
            throw new \RuntimeException('Deposit already processed');
        }

        $db->beginTransaction();
        try {
            $db->prepare('UPDATE deposits SET status="PAID", paid_at = NOW() WHERE id=?')->execute([$depositId]);
            $db->prepare('UPDATE balances SET available = available + ? WHERE user_id = ?')->execute([$dep['amount_usdt'], $dep['user_id']]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $this->telegram->sendMessage((int)$dep['telegram_id'], "Пополнение #{$depositId} зачислено: {$dep['amount_usdt']} USDT");
        $this->repo->audit('admin', 'deposit.approve', ['depositId' => $depositId]);
    }
}
