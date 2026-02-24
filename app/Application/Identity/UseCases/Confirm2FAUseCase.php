<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\Confirm2FAInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Exceptions\InvalidTwoFactorCodeException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Shared\ValueObjects\Uuid;

final class Confirm2FAUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TwoFactorServiceInterface $twoFactorService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(Confirm2FAInput $input): MessageOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($input->userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        if (! $this->twoFactorService->verifyCode($input->secret, $input->otpCode)) {
            throw new InvalidTwoFactorCodeException;
        }

        $encryptedSecret = $this->twoFactorService->encryptSecret($input->secret);
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $updated = $user->enableTwoFactor(
            secret: new TwoFactorSecret($encryptedSecret),
            recoveryCodes: json_encode($recoveryCodes, JSON_THROW_ON_ERROR),
        );

        $this->userRepository->update($updated);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return new MessageOutput('Two-factor authentication enabled. Save your recovery codes: '.implode(', ', $recoveryCodes));
    }
}
