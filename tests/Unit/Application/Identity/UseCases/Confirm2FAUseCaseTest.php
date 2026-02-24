<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\Confirm2FAInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\Confirm2FAUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\InvalidTwoFactorCodeException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->twoFactorService = Mockery::mock(TwoFactorServiceInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new Confirm2FAUseCase(
        $this->userRepository,
        $this->twoFactorService,
        $this->eventDispatcher,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';

    $this->user = User::reconstitute(
        id: Uuid::fromString($this->userId),
        name: 'John Doe',
        email: Email::fromString('john@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
        phone: null,
        timezone: 'America/Sao_Paulo',
        emailVerifiedAt: new DateTimeImmutable,
        twoFactorEnabled: false,
        twoFactorSecret: null,
        recoveryCodes: null,
        status: UserStatus::Active,
        lastLoginAt: null,
        lastLoginIp: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
});

it('confirms 2FA and returns recovery codes message', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->twoFactorService->shouldReceive('verifyCode')->once()->with('JBSWY3DPEHPK3PXP', '123456')->andReturn(true);
    $this->twoFactorService->shouldReceive('encryptSecret')->once()->andReturn('encrypted-secret');
    $this->twoFactorService->shouldReceive('generateRecoveryCodes')->once()->andReturn(['code1', 'code2', 'code3']);
    $this->userRepository->shouldReceive('update')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new Confirm2FAInput($this->userId, 'JBSWY3DPEHPK3PXP', '123456');
    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toContain('Two-factor authentication enabled')
        ->and($output->message)->toContain('code1');
});

it('throws on invalid OTP code', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn($this->user);
    $this->twoFactorService->shouldReceive('verifyCode')->once()->andReturn(false);

    $input = new Confirm2FAInput($this->userId, 'JBSWY3DPEHPK3PXP', '000000');
    $this->useCase->execute($input);
})->throws(InvalidTwoFactorCodeException::class);

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new Confirm2FAInput($this->userId, 'JBSWY3DPEHPK3PXP', '123456');
    $this->useCase->execute($input);
})->throws(AuthenticationException::class);
