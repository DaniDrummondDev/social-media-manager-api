<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\DTOs\UpdateSystemConfigInput;
use App\Application\PlatformAdmin\Exceptions\ConfigKeyNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Exceptions\InvalidConfigValueException;
use App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateSystemConfigUseCase
{
    public function __construct(
        private readonly SystemConfigRepositoryInterface $configRepository,
        private readonly AuditServiceInterface $auditService,
    ) {}

    public function execute(
        UpdateSystemConfigInput $input,
        PlatformRole $role,
        string $adminId,
        string $ipAddress,
        ?string $userAgent,
    ): void {
        if (! $role->canManageConfig()) {
            throw new InsufficientAdminPrivilegeException;
        }

        $adminUuid = Uuid::fromString($adminId);
        $updatedKeys = [];

        foreach ($input->configs as $key => $newValue) {
            $config = $this->configRepository->findByKey($key);

            if ($config === null) {
                throw new ConfigKeyNotFoundException($key);
            }

            $this->validateValueType($key, $newValue, $config->valueType);

            $oldValue = $config->isSecret ? '********' : $config->value;
            $updated = $config->updateValue($newValue, $adminUuid);

            $this->configRepository->upsert($updated);

            $updatedKeys[] = [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $config->isSecret ? '********' : $newValue,
            ];
        }

        $this->auditService->log(
            adminId: $adminId,
            action: 'system_config.updated',
            resourceType: 'system_config',
            resourceId: null,
            context: [
                'updated_configs' => $updatedKeys,
            ],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    private function validateValueType(string $key, mixed $value, string $expectedType): void
    {
        $valid = match ($expectedType) {
            'string' => is_string($value),
            'integer', 'int' => is_int($value),
            'float', 'double' => is_float($value) || is_int($value),
            'boolean', 'bool' => is_bool($value),
            'array' => is_array($value),
            'json' => is_array($value) || is_string($value),
            default => true,
        };

        if (! $valid) {
            throw new InvalidConfigValueException($key, $expectedType);
        }
    }
}
