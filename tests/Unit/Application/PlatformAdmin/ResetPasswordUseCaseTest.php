<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\ResetPasswordInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\ResetPasswordUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('requests password reset successfully as support', function () {
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
        ]);

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.password_reset_requested',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['user_email'] === 'john@example.com'),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new ResetPasswordUseCase($queryService, $auditService);
    $useCase->execute(
        new ResetPasswordInput($userId),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('requests password reset successfully as admin', function () {
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
        ]);

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.password_reset_requested',
            'user',
            $userId,
            Mockery::type('array'),
            '127.0.0.1',
            null,
        )
        ->once();

    $useCase = new ResetPasswordUseCase($queryService, $auditService);
    $useCase->execute(
        new ResetPasswordInput($userId),
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

    $useCase = new ResetPasswordUseCase($queryService, $auditService);
    $useCase->execute(
        new ResetPasswordInput($userId),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(AdminUserNotFoundException::class);
