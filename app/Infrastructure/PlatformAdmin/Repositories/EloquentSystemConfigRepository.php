<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\SystemConfig;
use App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PlatformAdmin\Models\SystemConfigModel;
use DateTimeImmutable;

final class EloquentSystemConfigRepository implements SystemConfigRepositoryInterface
{
    public function __construct(
        private readonly SystemConfigModel $model,
    ) {}

    public function findByKey(string $key): ?SystemConfig
    {
        /** @var SystemConfigModel|null $record */
        $record = $this->model->newQuery()->find($key);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<SystemConfig>
     */
    public function findAll(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, SystemConfigModel> $records */
        $records = $this->model->newQuery()->orderBy('key')->get();

        return $records->map(fn (SystemConfigModel $r) => $this->toDomain($r))->all();
    }

    public function upsert(SystemConfig $config): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['key' => $config->key],
            [
                'value' => json_encode($config->value),
                'value_type' => $config->valueType,
                'description' => $config->description,
                'is_secret' => $config->isSecret,
                'updated_by' => $config->updatedBy ? (string) $config->updatedBy : null,
            ],
        );
    }

    private function toDomain(SystemConfigModel $model): SystemConfig
    {
        $rawValue = $model->getAttribute('value');
        $decoded = is_string($rawValue) ? json_decode($rawValue, true) : $rawValue;

        $updatedBy = $model->getAttribute('updated_by');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return SystemConfig::reconstitute(
            key: $model->getAttribute('key'),
            value: $decoded,
            valueType: $model->getAttribute('value_type'),
            description: $model->getAttribute('description'),
            isSecret: (bool) $model->getAttribute('is_secret'),
            updatedBy: $updatedBy ? Uuid::fromString($updatedBy) : null,
            createdAt: new DateTimeImmutable($createdAt->toDateTimeString()),
            updatedAt: new DateTimeImmutable($updatedAt->toDateTimeString()),
        );
    }
}
