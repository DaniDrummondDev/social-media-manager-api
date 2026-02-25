<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\DTOs\UpdateSystemConfigInput;
use App\Application\PlatformAdmin\Exceptions\ConfigKeyNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\UpdateSystemConfigUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Entities\SystemConfig;
use App\Domain\PlatformAdmin\Exceptions\InvalidConfigValueException;
use App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('updates a system config successfully', function () {
    $configRepository = Mockery::mock(SystemConfigRepositoryInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $existingConfig = SystemConfig::reconstitute(
        key: 'default_trial_days',
        value: 14,
        valueType: 'integer',
        description: 'Default trial period in days',
        isSecret: false,
        updatedBy: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $configRepository->shouldReceive('findByKey')
        ->with('default_trial_days')
        ->once()
        ->andReturn($existingConfig);

    $configRepository->shouldReceive('upsert')
        ->once()
        ->with(Mockery::on(fn (SystemConfig $c) => $c->key === 'default_trial_days' && $c->value === 30));

    $auditService->shouldReceive('log')
        ->with(
            $adminId,
            'system_config.updated',
            'system_config',
            null,
            Mockery::on(fn (array $ctx) => isset($ctx['updated_configs'])
                && $ctx['updated_configs'][0]['key'] === 'default_trial_days'
                && $ctx['updated_configs'][0]['old_value'] === 14
                && $ctx['updated_configs'][0]['new_value'] === 30),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new UpdateSystemConfigUseCase($configRepository, $auditService);
    $useCase->execute(
        new UpdateSystemConfigInput(['default_trial_days' => 30]),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws ConfigKeyNotFoundException when key does not exist', function () {
    $configRepository = Mockery::mock(SystemConfigRepositoryInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $configRepository->shouldReceive('findByKey')
        ->with('nonexistent_key')
        ->once()
        ->andReturn(null);

    $useCase = new UpdateSystemConfigUseCase($configRepository, $auditService);
    $useCase->execute(
        new UpdateSystemConfigInput(['nonexistent_key' => 'value']),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(ConfigKeyNotFoundException::class);

it('throws InsufficientAdminPrivilegeException when role is admin', function () {
    $configRepository = Mockery::mock(SystemConfigRepositoryInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $useCase = new UpdateSystemConfigUseCase($configRepository, $auditService);
    $useCase->execute(
        new UpdateSystemConfigInput(['default_trial_days' => 30]),
        PlatformRole::Admin,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);

it('throws InvalidConfigValueException when value type is wrong', function () {
    $configRepository = Mockery::mock(SystemConfigRepositoryInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $existingConfig = SystemConfig::reconstitute(
        key: 'default_trial_days',
        value: 14,
        valueType: 'integer',
        description: null,
        isSecret: false,
        updatedBy: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $configRepository->shouldReceive('findByKey')
        ->with('default_trial_days')
        ->once()
        ->andReturn($existingConfig);

    $useCase = new UpdateSystemConfigUseCase($configRepository, $auditService);
    $useCase->execute(
        new UpdateSystemConfigInput(['default_trial_days' => 'not-an-integer']),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        null,
    );
})->throws(InvalidConfigValueException::class);

it('masks secret config values in audit log', function () {
    $configRepository = Mockery::mock(SystemConfigRepositoryInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $adminId = '00000000-0000-4000-a000-000000000001';

    $secretConfig = SystemConfig::reconstitute(
        key: 'stripe_api_key',
        value: 'sk_live_old_key',
        valueType: 'string',
        description: 'Stripe API key',
        isSecret: true,
        updatedBy: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $configRepository->shouldReceive('findByKey')
        ->with('stripe_api_key')
        ->once()
        ->andReturn($secretConfig);

    $configRepository->shouldReceive('upsert')
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            $adminId,
            'system_config.updated',
            'system_config',
            null,
            Mockery::on(fn (array $ctx) => $ctx['updated_configs'][0]['old_value'] === '********'
                && $ctx['updated_configs'][0]['new_value'] === '********'),
            '127.0.0.1',
            null,
        )
        ->once();

    $useCase = new UpdateSystemConfigUseCase($configRepository, $auditService);
    $useCase->execute(
        new UpdateSystemConfigInput(['stripe_api_key' => 'sk_live_new_key']),
        PlatformRole::SuperAdmin,
        $adminId,
        '127.0.0.1',
        null,
    );

    expect(true)->toBeTrue();
});
