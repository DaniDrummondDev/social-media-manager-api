<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\ForceVerifyInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\ForceVerifyUserUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('force verifies an unverified user successfully', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000010';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified' => false,
        ]);

    $queryService->shouldReceive('forceVerifyUser')
        ->with($userId)
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.force_verified',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['user_email'] === 'john@example.com'
                && $ctx['was_verified'] === false),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new ForceVerifyUserUseCase($queryService, $auditService);
    $useCase->execute(
        new ForceVerifyInput($userId),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('force verifies an already verified user and logs it', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000010';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'email_verified' => true,
        ]);

    $queryService->shouldReceive('forceVerifyUser')
        ->with($userId)
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.force_verified',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['was_verified'] === true),
            '127.0.0.1',
            null,
        )
        ->once();

    $useCase = new ForceVerifyUserUseCase($queryService, $auditService);
    $useCase->execute(
        new ForceVerifyInput($userId),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        null,
    );

    expect(true)->toBeTrue();
});

it('throws AdminUserNotFoundException when user does not exist', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000099';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $useCase = new ForceVerifyUserUseCase($queryService, $auditService);
    $useCase->execute(
        new ForceVerifyInput($userId),
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(AdminUserNotFoundException::class);
