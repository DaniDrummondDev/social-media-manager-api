<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface;

final class GetSystemConfigUseCase
{
    public function __construct(
        private readonly SystemConfigRepositoryInterface $configRepository,
    ) {}

    /**
     * @return array<array{key: string, value: mixed, value_type: string, description: ?string, is_secret: bool, updated_at: string}>
     */
    public function execute(): array
    {
        $configs = $this->configRepository->findAll();

        return array_map(fn ($config) => [
            'key' => $config->key,
            'value' => $config->isSecret ? '********' : $config->value,
            'value_type' => $config->valueType,
            'description' => $config->description,
            'is_secret' => $config->isSecret,
            'updated_at' => $config->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ], $configs);
    }
}
