<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\RegisterUserInput;
use App\Application\Identity\DTOs\UserOutput;
use App\Application\Identity\Exceptions\EmailAlreadyInUseException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\PasswordPolicyService;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;

final class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EmailVerificationServiceInterface $emailVerificationService,
    ) {}

    public function execute(RegisterUserInput $input): UserOutput
    {
        $this->passwordPolicy->validate($input->password, $input->email);

        $email = Email::fromString($input->email);

        if ($this->userRepository->existsByEmail($email)) {
            throw new EmailAlreadyInUseException($input->email);
        }

        $user = User::create(
            name: $input->name,
            email: $email,
            password: HashedPassword::fromPlainText($input->password),
        );

        $this->userRepository->create($user);
        $this->eventDispatcher->dispatch(...$user->domainEvents);
        $this->emailVerificationService->sendVerificationEmail((string) $user->id, (string) $user->email);

        return UserOutput::fromEntity($user);
    }
}
