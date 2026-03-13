<?php

declare(strict_types=1);

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\UnbanUserInput;
use App\Application\PlatformAdmin\Exceptions\AdminUserNotFoundException;
use App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException;
use App\Application\PlatformAdmin\UseCases\UnbanUserUseCase;
use App\Domain\PlatformAdmin\Contracts\AuditServiceInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\Exceptions\DomainException;

it('unbans a suspended user successfully', function () {
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
            'ban_reason' => 'Spam behavior',
        ]);

    $queryService->shouldReceive('unbanUser')
        ->with($userId)
        ->once();

    $auditService->shouldReceive('log')
        ->with(
            'admin-id',
            'user.unbanned',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['user_email'] === 'banned@example.com'
                && $ctx['previous_ban_reason'] === 'Spam behavior'),
            '127.0.0.1',
            'TestAgent',
        )
        ->once();

    $useCase = new UnbanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new UnbanUserInput($userId),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        'TestAgent',
    );

    expect(true)->toBeTrue();
});

it('throws DomainException when user is not suspended', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000010';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'Active User',
            'email' => 'active@example.com',
            'status' => 'active',
        ]);

    $useCase = new UnbanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new UnbanUserInput($userId),
        PlatformRole::Admin,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(DomainException::class);

it('throws AdminUserNotFoundException when user does not exist', function () {
    $queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $auditService = Mockery::mock(AuditServiceInterface::class);

    $userId = '00000000-0000-4000-a000-000000000099';

    $queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $useCase = new UnbanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new UnbanUserInput($userId),
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

    $useCase = new UnbanUserUseCase($queryService, $auditService);
    $useCase->execute(
        new UnbanUserInput($userId),
        PlatformRole::Support,
        'admin-id',
        '127.0.0.1',
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);
