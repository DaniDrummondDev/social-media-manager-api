<?php

declare(strict_types=1);

use App\Domain\PlatformAdmin\Entities\SystemConfig;
use App\Domain\PlatformAdmin\Events\SystemConfigUpdated;
use App\Domain\Shared\ValueObjects\Uuid;

it('reconstitutes a system config', function () {
    $config = SystemConfig::reconstitute(
        key: 'maintenance_mode', value: false, valueType: 'boolean',
        description: 'Test', isSecret: false, updatedBy: null,
        createdAt: new DateTimeImmutable, updatedAt: new DateTimeImmutable,
    );
    expect($config->key)->toBe('maintenance_mode')
        ->and($config->value)->toBeFalse()
        ->and($config->domainEvents)->toBeEmpty();
});

it('dispatches SystemConfigUpdated on updateValue', function () {
    $config = SystemConfig::reconstitute(
        key: 'default_trial_days', value: 14, valueType: 'integer',
        description: null, isSecret: false, updatedBy: null,
        createdAt: new DateTimeImmutable, updatedAt: new DateTimeImmutable,
    );

    $adminId = Uuid::generate();
    $updated = $config->updateValue(7, $adminId);

    expect($updated->value)->toBe(7)
        ->and($updated->updatedBy->equals($adminId))->toBeTrue()
        ->and($updated->domainEvents)->toHaveCount(1)
        ->and($updated->domainEvents[0])->toBeInstanceOf(SystemConfigUpdated::class)
        ->and($updated->domainEvents[0]->configKey)->toBe('default_trial_days')
        ->and($updated->domainEvents[0]->oldValue)->toBe(14)
        ->and($updated->domainEvents[0]->newValue)->toBe(7);
});
