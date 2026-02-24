<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\TwoFactorSetupOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\UseCases\Enable2FAUseCase;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->twoFactorService = Mockery::mock(TwoFactorServiceInterface::class);

    $this->useCase = new Enable2FAUseCase(
        $this->userRepository,
        $this->twoFactorService,
    );

    $this->userId = '550e8400-e29b-41d4-a716-446655440000';
});

it('returns 2FA setup data', function () {
    $user = User::reconstitute(
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

    $this->userRepository->shouldReceive('findById')->once()->andReturn($user);
    $this->twoFactorService->shouldReceive('generateSecret')->once()->andReturn('JBSWY3DPEHPK3PXP');
    $this->twoFactorService->shouldReceive('generateQrCodeUri')->once()->andReturn('otpauth://totp/App:john@example.com?secret=JBSWY3DPEHPK3PXP');
    $this->twoFactorService->shouldReceive('generateQrCodeSvg')->once()->andReturn('<svg>qr</svg>');

    $output = $this->useCase->execute($this->userId);

    expect($output)->toBeInstanceOf(TwoFactorSetupOutput::class)
        ->and($output->secret)->toBe('JBSWY3DPEHPK3PXP')
        ->and($output->qrCodeUrl)->toContain('otpauth://')
        ->and($output->qrCodeSvg)->toContain('<svg>');
});

it('throws when user not found', function () {
    $this->userRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute($this->userId);
})->throws(AuthenticationException::class);
