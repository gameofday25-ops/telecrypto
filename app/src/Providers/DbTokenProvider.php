<?php

declare(strict_types=1);

namespace App\Src\Providers;

use App\Src\Repositories\AppRepository;

class DbTokenProvider implements TokenProviderInterface
{
    public function __construct(private AppRepository $repo)
    {
    }

    public function list(string $search = '', bool $activeOnly = true): array
    {
        return $this->repo->listTokens($search, $activeOnly);
    }

    public function get(int $id): ?array
    {
        return $this->repo->getToken($id);
    }
}
