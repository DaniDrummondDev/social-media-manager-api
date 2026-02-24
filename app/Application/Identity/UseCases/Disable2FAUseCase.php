<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\DTOs\Disable2FAInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class Disable2FAUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(Disable2FAInput $input): MessageOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($input->userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        if (! $user->password->verify($input->password)) {
            throw new AuthenticationException('Invalid password');
        }

        $updated = $user->disableTwoFactor();

        $this->userRepository->update($updated);
        $this->eventDispatcher->dispatch(...$updated->domainEvents);

        return new MessageOutput('Two-factor authentication disabled');
    }
}
