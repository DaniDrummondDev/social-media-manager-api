<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\DTOs\UpdateListeningQueryInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;

final class UpdateListeningQueryUseCase
{
    public function __construct(
        private readonly ListeningQueryRepositoryInterface $queryRepository,
    ) {}

    public function execute(UpdateListeningQueryInput $input): ListeningQueryOutput
    {
        $queryId = Uuid::fromString($input->queryId);
        $organizationId = Uuid::fromString($input->organizationId);

        $query = $this->queryRepository->findById($queryId);

        if ($query === null || (string) $query->organizationId !== (string) $organizationId) {
            throw new ListeningQueryNotFoundException();
        }

        $query = $query->updateDetails(
            name: $input->name,
            value: $input->value,
            platforms: $input->platforms,
        );

        $this->queryRepository->update($query);

        return ListeningQueryOutput::fromEntity($query);
    }
}
