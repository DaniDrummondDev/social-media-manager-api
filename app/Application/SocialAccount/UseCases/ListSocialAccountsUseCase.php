<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\UseCases;

use App\Application\SocialAccount\DTOs\SocialAccountListOutput;
use App\Application\SocialAccount\DTOs\SocialAccountOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;

final class ListSocialAccountsUseCase
{
    public function __construct(
        private readonly SocialAccountRepositoryInterface $socialAccountRepository,
    ) {}

    public function execute(string $organizationId): SocialAccountListOutput
    {
        $accounts = $this->socialAccountRepository->findByOrganizationId(
            Uuid::fromString($organizationId),
        );

        $outputs = array_map(
            fn ($account) => SocialAccountOutput::fromEntity($account),
            $accounts,
        );

        return new SocialAccountListOutput(accounts: $outputs);
    }
}
