<?php

declare(strict_types=1);

namespace App\Src\Providers;

interface TokenProviderInterface
{
    public function list(string $search = '', bool $activeOnly = true): array;
    public function get(int $id): ?array;
}
