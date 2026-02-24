<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\TwoFactorSetupOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class Enable2FAUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TwoFactorServiceInterface $twoFactorService,
    ) {}

    public function execute(string $userId): TwoFactorSetupOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUri = $this->twoFactorService->generateQrCodeUri($secret, (string) $user->email);
        $qrCodeSvg = $this->twoFactorService->generateQrCodeSvg($qrCodeUri);

        return new TwoFactorSetupOutput(
            secret: $secret,
            qrCodeUrl: $qrCodeUri,
            qrCodeSvg: $qrCodeSvg,
        );
    }
}
