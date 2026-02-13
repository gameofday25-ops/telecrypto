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
        if (!$user) throw new \RuntimeException('User not found');
        return $this->repo->toggleFavorite((int)$user['id'], $tokenId);
    }

    public function createOrder(int $telegramId, array $items): int
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) throw new \RuntimeException('User not found');
        return $this->repo->createOrder((int)$user['id'], $items);
    }

    public function listOrders(int $telegramId): array
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) throw new \RuntimeException('User not found');
        return $this->repo->listOrders((int)$user['id']);
    }

    public function createDeposit(int $telegramId, string $amount, string $method): int
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) throw new \RuntimeException('User not found');
        return $this->repo->createDeposit((int)$user['id'], $amount, $method);
    }

    public function listDeposits(int $telegramId): array
    {
        $user = $this->repo->getUserByTelegramId($telegramId);
        if (!$user) throw new \RuntimeException('User not found');
        return $this->repo->listDeposits((int)$user['id']);
    }
}
