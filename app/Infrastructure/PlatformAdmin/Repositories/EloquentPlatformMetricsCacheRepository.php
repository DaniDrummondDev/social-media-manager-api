<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Repositories\PlatformMetricsCacheRepositoryInterface;
use App\Infrastructure\PlatformAdmin\Models\PlatformMetricsCacheModel;
use Carbon\Carbon;

final class EloquentPlatformMetricsCacheRepository implements PlatformMetricsCacheRepositoryInterface
{
    public function __construct(
        private readonly PlatformMetricsCacheModel $model,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        /** @var PlatformMetricsCacheModel|null $record */
        $record = $this->model->newQuery()->find($key);

        if ($record === null) {
            return null;
        }

        $computedAt = $record->getAttribute('computed_at');
        $ttlSeconds = (int) $record->getAttribute('ttl_seconds');

        $expiresAt = Carbon::parse($computedAt)->addSeconds($ttlSeconds);

        if ($expiresAt->isPast()) {
            return null;
        }

        return $record->getAttribute('value') ?? [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function set(string $key, array $value, int $ttlSeconds): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'computed_at' => Carbon::now(),
                'ttl_seconds' => $ttlSeconds,
            ],
        );
    }
}
