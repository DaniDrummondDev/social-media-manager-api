<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\DTOs\ForgotPasswordInput;
use App\Application\Identity\DTOs\MessageOutput;
use App\Application\Identity\UseCases\ForgotPasswordUseCase;

beforeEach(function () {
    $this->passwordResetService = Mockery::mock(PasswordResetServiceInterface::class);

    $this->useCase = new ForgotPasswordUseCase($this->passwordResetService);
});

it('always returns success to prevent email enumeration', function () {
    $this->passwordResetService->shouldReceive('sendResetEmail')->once()->with('john@example.com');

    $output = $this->useCase->execute(new ForgotPasswordInput('john@example.com'));

    expect($output)->toBeInstanceOf(MessageOutput::class)
        ->and($output->message)->toContain('reset link');
});

it('returns success even for non-existent email', function () {
    $this->passwordResetService->shouldReceive('sendResetEmail')->once()->with('unknown@example.com');

    $output = $this->useCase->execute(new ForgotPasswordInput('unknown@example.com'));

    expect($output)->toBeInstanceOf(MessageOutput::class);
});
