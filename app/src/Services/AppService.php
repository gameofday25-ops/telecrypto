<?php

declare(strict_types=1);

namespace App\Src\Services;

use App\Src\Providers\TokenProviderInterface;
use App\Src\Repositories\AppRepository;

class AppService
{
    public function __construct(private AppRepository $repo, private TokenProviderInterface $tokenProvider)
    {
    }

    public function getMe(int $telegramId): array
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not registered. Send /start to bot first.');
        }
        $user['balance'] = $this->repo->getBalance((int)$user['id']);
        return $user;
    }

    public function tokens(string $search, bool $active): array
    {
        return $this->tokenProvider->list($search, $active);
    }

    public function token(int $id): ?array
    {
        return $this->tokenProvider->get($id);
    }

    public function collections(): array
    {
        return $this->repo->listCollections();
    }

    public function toggleFavorite(int $telegramId, int $tokenId): bool
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        return $this->repo->toggleFavorite((int)$user['id'], $tokenId);
    }

    public function createOrder(int $telegramId, array $items): int
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        if (!$items) {
            throw new \RuntimeException('Order items are required');
        }

        foreach ($items as $item) {
            $tokenId = (int)($item['tokenId'] ?? 0);
            $amount = (string)($item['amountUsdt'] ?? '0');
            $token = $this->tokenProvider->get($tokenId);
            if (!$token || (int)$token['is_active'] !== 1) {
                throw new \RuntimeException('Token unavailable: ' . $tokenId);
            }
            if (!preg_match('/^\d+(\.\d{1,8})?$/', $amount) || bccomp($amount, '0', 8) <= 0) {
                throw new \RuntimeException('Invalid amount for token ' . $tokenId);
            }
        }

        return $this->repo->createOrder((int)$user['id'], $items);
    }

    public function listOrders(int $telegramId): array
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        return $this->repo->listOrders((int)$user['id']);
    }

    public function createDeposit(int $telegramId, string $amount, string $method): int
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        if (!preg_match('/^\d+(\.\d{1,8})?$/', $amount) || bccomp($amount, '0', 8) <= 0) {
            throw new \RuntimeException('Invalid deposit amount');
        }

        $normalizedMethod = strtoupper(trim($method));
        if ($normalizedMethod === 'TEST' || $normalizedMethod === '') {
            throw new \RuntimeException('Use real payment method request (e.g. TRC20, ERC20, BANK)');
        }

        return $this->repo->createDeposit((int)$user['id'], $amount, $normalizedMethod);
    }

    public function listDeposits(int $telegramId): array
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        return $this->repo->listDeposits((int)$user['id']);
    }
}
