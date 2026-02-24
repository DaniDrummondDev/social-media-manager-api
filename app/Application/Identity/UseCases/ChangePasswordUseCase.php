<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\ChangePasswordInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\PasswordPolicyService;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Shared\ValueObjects\Uuid;

final class ChangePasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ChangePasswordInput $input): MessageOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($input->userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        if (! $user->password->verify($input->currentPassword)) {
            throw new AuthenticationException('Invalid current password');
        }

        $this->passwordPolicy->validate($input->newPassword, (string) $user->email);

        $updated = $user->changePassword(HashedPassword::fromPlainText($input->newPassword));

        $this->userRepository->update($updated);
        $this->refreshTokenRepository->revokeAllForUser((string) $user->id);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return new MessageOutput('Password changed successfully');
    }
}
