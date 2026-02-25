<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteBlacklistWordUseCase
{
    public function __construct(
        private readonly BlacklistWordRepositoryInterface $blacklistRepository,
    ) {}

    public function execute(string $organizationId, string $wordId): void
    {
        $id = Uuid::fromString($wordId);
        $word = $this->blacklistRepository->findById($id);

        if ($word === null || (string) $word->organizationId !== $organizationId) {
            return;
        }

        $this->blacklistRepository->delete($id);
    }
}
