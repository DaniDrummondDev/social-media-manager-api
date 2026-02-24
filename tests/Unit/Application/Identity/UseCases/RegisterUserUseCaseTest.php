<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\EmailVerificationServiceInterface;
use App\Application\Identity\DTOs\RegisterUserInput;
use App\Application\Identity\DTOs\UserOutput;
use App\Application\Identity\Exceptions\EmailAlreadyInUseException;
use App\Application\Identity\UseCases\RegisterUserUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Identity\Exceptions\WeakPasswordException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\Services\PasswordPolicyService;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->passwordPolicy = new PasswordPolicyService;
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->emailVerification = Mockery::mock(EmailVerificationServiceInterface::class);

    $this->useCase = new RegisterUserUseCase(
        $this->userRepository,
        $this->passwordPolicy,
        $this->eventDispatcher,
        $this->emailVerification,
    );
});

it('registers a new user successfully', function () {
    $this->userRepository->shouldReceive('existsByEmail')->once()->andReturn(false);
    $this->userRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();
    $this->emailVerification->shouldReceive('sendVerificationEmail')->once();

    $input = new RegisterUserInput(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'SecureP@ss1',
        passwordConfirmation: 'SecureP@ss1',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(UserOutput::class)
        ->and($output->name)->toBe('John Doe')
        ->and($output->email)->toBe('john@example.com')
        ->and($output->emailVerified)->toBeFalse()
        ->and($output->twoFactorEnabled)->toBeFalse();
});

it('throws when email already exists', function () {
    $this->userRepository->shouldReceive('existsByEmail')->once()->andReturn(true);

    $input = new RegisterUserInput(
        name: 'John Doe',
        email: 'existing@example.com',
        password: 'SecureP@ss1',
        passwordConfirmation: 'SecureP@ss1',
    );

    $this->useCase->execute($input);
})->throws(EmailAlreadyInUseException::class);

it('throws when password is weak', function () {
    $input = new RegisterUserInput(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'weak',
        passwordConfirmation: 'weak',
    );

    $this->useCase->execute($input);
})->throws(WeakPasswordException::class);
