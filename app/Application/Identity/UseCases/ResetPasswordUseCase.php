<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\DTOs\ResetPasswordInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\PasswordPolicyService;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Shared\ValueObjects\Uuid;

final class ResetPasswordUseCase
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ResetPasswordInput $input): MessageOutput
    {
        $userId = $this->passwordResetService->verifyToken($input->token);

        $user = $this->userRepository->findById(Uuid::fromString($userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $this->passwordPolicy->validate($input->password, (string) $user->email);

        $updated = $user->changePassword(HashedPassword::fromPlainText($input->password));

        $this->userRepository->update($updated);
        $this->passwordResetService->invalidateTokensForUser($userId);
        $this->refreshTokenRepository->revokeAllForUser($userId);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return new MessageOutput('Password reset successfully');
    }
}
