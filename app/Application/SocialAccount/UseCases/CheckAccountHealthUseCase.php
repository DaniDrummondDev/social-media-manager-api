<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\SocialAccount\DTOs\AccountHealthOutput;
use App\Application\SocialAccount\DTOs\CheckAccountHealthInput;
use App\Application\SocialAccount\Exceptions\SocialAccountAuthorizationException;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class CheckAccountHealthUseCase
{
    public function __construct(
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
    ) {}

    public function execute(CheckAccountHealthInput $input): AccountHealthOutput
    {
        $account = $this->socialAccountRepository->findById(Uuid::fromString($input->accountId));

        if ($account === null) {
            throw new SocialAccountNotFoundException($input->accountId);
        }

        if ((string) $account->organizationId !== $input->organizationId) {
            throw new SocialAccountAuthorizationException;
        }

        return new AccountHealthOutput(
            accountId: (string) $account->id,
            status: $account->status->value,
            canPublish: $account->isActive(),
            tokenExpiresAt: $account->credentials->expiresAt?->format('c'),
            isExpired: $account->credentials->isExpired(),
            willExpireSoon: $account->credentials->willExpireSoon(),
        );
    }
}
