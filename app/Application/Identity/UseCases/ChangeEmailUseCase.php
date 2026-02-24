<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\ChangeEmailInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Identity\Exceptions\EmailAlreadyInUseException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\Uuid;

final class ChangeEmailUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailVerificationServiceInterface $emailVerificationService,
    ) {}

    public function execute(ChangeEmailInput $input): MessageOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($input->userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        if (! $user->password->verify($input->password)) {
            throw new AuthenticationException('Invalid password');
        }

        $newEmail = Email::fromString($input->newEmail);

        if ($this->userRepository->existsByEmail($newEmail)) {
            throw new EmailAlreadyInUseException($input->newEmail);
        }

        $this->emailVerificationService->sendVerificationEmail((string) $user->id, (string) $newEmail);

        return new MessageOutput('Verification email sent to '.$input->newEmail);
    }
}
