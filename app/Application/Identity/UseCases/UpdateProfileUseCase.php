<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\DTOs\UpdateProfileInput;
use App\Application\Identity\DTOs\UserOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateProfileUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateProfileInput $input): UserOutput
    {
        $user = $this->userRepository->findById(Uuid::fromString($input->userId));

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $updated = $user->updateProfile(
            name: $input->name,
            phone: $input->phone,
            timezone: $input->timezone,
        );

        if ($updated !== $user) {
            $this->userRepository->update($updated);
            $this->eventDispatcher->dispatch(...$updated->domainEvents);
        }

        return UserOutput::fromEntity($updated);
    }
}
