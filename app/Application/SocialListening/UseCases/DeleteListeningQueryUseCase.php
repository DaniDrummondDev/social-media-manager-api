<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\DeleteListeningQueryInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;

final class DeleteListeningQueryUseCase
{
    public function __construct(
        private readonly ListeningQueryRepositoryInterface $queryRepository,
    ) {}

    public function execute(DeleteListeningQueryInput $input): void
    {
        $queryId = Uuid::fromString($input->queryId);
        $organizationId = Uuid::fromString($input->organizationId);

        $query = $this->queryRepository->findById($queryId);

        if ($query === null || (string) $query->organizationId !== (string) $organizationId) {
            throw new ListeningQueryNotFoundException();
        }

        $query = $query->markDeleted();

        $this->queryRepository->update($query);
    }
}
