<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\SystemConfig;

interface SystemConfigRepositoryInterface
{
    public function findByKey(string $key): ?SystemConfig;

    /**
     * @return array<SystemConfig>
     */
    public function findAll(): array;

    public function upsert(SystemConfig $config): void;
}
