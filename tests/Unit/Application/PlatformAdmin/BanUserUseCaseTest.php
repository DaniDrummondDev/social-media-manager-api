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

beforeEach(function () {
    $this->queryService = Mockery::mock(PlatformQueryServiceInterface::class);
    $this->auditService = Mockery::mock(AuditServiceInterface::class);
    $this->useCase = new BanUserUseCase($this->queryService, $this->auditService);

    $this->adminId = 'admin-id';
    $this->ipAddress = '127.0.0.1';
    $this->userAgent = 'TestAgent';
});

it('bans an active user successfully with super admin role', function () {
    $userId = '00000000-0000-4000-a000-000000000010';

    $this->queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

    $this->queryService->shouldReceive('banUser')
        ->with($userId, 'Spam behavior')
        ->once();

    $this->auditService->shouldReceive('log')
        ->with(
            $this->adminId,
            'user.banned',
            'user',
            $userId,
            Mockery::on(fn (array $ctx) => $ctx['reason'] === 'Spam behavior'
                && $ctx['user_email'] === 'john@example.com'
                && $ctx['previous_status'] === 'active'),
            $this->ipAddress,
            $this->userAgent,
        )
        ->once();

    $this->useCase->execute(
        new BanUserInput($userId, 'Spam behavior'),
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );

    expect(true)->toBeTrue();
});

it('bans an active user successfully with admin role', function () {
    $userId = '00000000-0000-4000-a000-000000000011';

    $this->queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
        ]);

    $this->queryService->shouldReceive('banUser')
        ->with($userId, 'Spam behavior')
        ->once();

    $this->auditService->shouldReceive('log')
        ->once();

    $this->useCase->execute(
        new BanUserInput($userId, 'Spam behavior'),
        PlatformRole::Admin,
        $this->adminId,
        $this->ipAddress,
        $this->userAgent,
    );

    expect(true)->toBeTrue();
});

it('throws UserAlreadyBannedException when user status is suspended', function () {
    $userId = '00000000-0000-4000-a000-000000000010';

    $this->queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn([
            'id' => $userId,
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'status' => 'suspended',
        ]);

    $this->useCase->execute(
        new BanUserInput($userId, 'Already banned'),
        PlatformRole::Admin,
        $this->adminId,
        $this->ipAddress,
        null,
    );
})->throws(UserAlreadyBannedException::class);

it('throws AdminUserNotFoundException when user does not exist', function () {
    $userId = '00000000-0000-4000-a000-000000000099';

    $this->queryService->shouldReceive('getUserDetail')
        ->with($userId)
        ->once()
        ->andReturn(null);

    $this->useCase->execute(
        new BanUserInput($userId, 'Reason'),
        PlatformRole::SuperAdmin,
        $this->adminId,
        $this->ipAddress,
        null,
    );
})->throws(AdminUserNotFoundException::class);

it('throws InsufficientAdminPrivilegeException when role is support', function () {
    $userId = '00000000-0000-4000-a000-000000000010';

    $this->useCase->execute(
        new BanUserInput($userId, 'Reason'),
        PlatformRole::Support,
        $this->adminId,
        $this->ipAddress,
        null,
    );
})->throws(InsufficientAdminPrivilegeException::class);
