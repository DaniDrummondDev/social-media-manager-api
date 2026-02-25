<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\BlacklistWordOutput;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListBlacklistUseCase
{
    public function __construct(
        private readonly BlacklistWordRepositoryInterface $blacklistRepository,
    ) {}

    /**
     * @return array<BlacklistWordOutput>
     */
    public function execute(string $organizationId): array
    {
        $orgId = Uuid::fromString($organizationId);
        $words = $this->blacklistRepository->findByOrganizationId($orgId);

        return array_map(
            fn ($word) => BlacklistWordOutput::fromEntity($word),
            $words,
        );
    }
}
