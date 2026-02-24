<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\DTOs\VerifyEmailInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class VerifyEmailUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailVerificationServiceInterface $emailVerificationService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(VerifyEmailInput $input): MessageOutput
    {
        $userId = $this->emailVerificationService->verifyToken($input->token);

        $user = $this->userRepository->findById(Uuid::fromString($userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $verified = $user->verifyEmail();

        $this->userRepository->update($verified);
        $this->eventDispatcher->dispatch(...$verified->domainEvents);

        return new MessageOutput('Email verified successfully');
    }
}
