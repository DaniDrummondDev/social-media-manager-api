<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Application\SocialListening\DTOs\PauseListeningQueryInput;
use App\Application\SocialListening\Exceptions\ListeningQueryNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;

final class PauseListeningQueryUseCase
{
    public function __construct(
        private readonly ListeningQueryRepositoryInterface $queryRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(PauseListeningQueryInput $input): ListeningQueryOutput
    {
        $queryId = Uuid::fromString($input->queryId);
        $organizationId = Uuid::fromString($input->organizationId);

        $query = $this->queryRepository->findById($queryId);

        if ($query === null || (string) $query->organizationId !== (string) $organizationId) {
            throw new ListeningQueryNotFoundException();
        }

        $query = $query->pause($input->userId);

        $this->queryRepository->update($query);

        $this->eventDispatcher->dispatch(...$query->domainEvents);

        return ListeningQueryOutput::fromEntity($query);
    }
}
