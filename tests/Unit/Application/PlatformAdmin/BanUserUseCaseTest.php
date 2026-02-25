<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\BanUserInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\BanUserUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\Exceptions\UserAlreadyBannedException;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;

it('bans an active user successfully', function () {
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
            'status' => 'active',
        ]);

    $queryService->shouldReceive('banUser')
        ->with($userId, 'Spam behavior')
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.banned',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['reason'] === 'Spam behavior'
                && $ctx['user_email'] === 'john@example.com'
                && $ctx['previous_status'] === 'active'),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new BanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new BanUserInput($userId, 'Spam behavior'),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws UserAlreadyBannedException when user status is suspended', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000010';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'status' => 'suspended',
        ]);

    $useCase = new BanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new BanUserInput($userId, 'Already banned'),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(UserAlreadyBannedException::class);

it('throws AdminUserNotFoundException when user does not exist', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000099';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $useCase = new BanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new BanUserInput($userId, 'Reason'),
        PlatformRole::SuperAdmin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(AdminUserNotFoundException::class);

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000010';

    $useCase = new BanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new BanUserInput($userId, 'Reason'),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);
