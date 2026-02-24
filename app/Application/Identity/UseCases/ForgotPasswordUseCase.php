<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\PasswordResetServiceInterface;
use App\Application\Identity\DTOs\ForgotPasswordInput;
use App\Application\Identity\DTOs\MessageOutput;

final class ForgotPasswordUseCase
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService,
    ) {}

    public function execute(ForgotPasswordInput $input): MessageOutput
    {
        $this->passwordResetService->sendResetEmail($input->email);

        return new MessageOutput('If the email exists, a reset link has been sent');
    }
}
