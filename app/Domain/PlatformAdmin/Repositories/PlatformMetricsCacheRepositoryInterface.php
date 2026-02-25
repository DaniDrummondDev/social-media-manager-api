<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Repositories;

interface PlatformMetricsCacheRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * @param  array<string, mixed>  $value
     */
    public function set(string $key, array $value, int $ttlSeconds): void;
}
