<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\SocialListening\DTOs\CreateListeningQueryInput;
use App\Application\SocialListening\DTOs\ListeningQueryOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryType;

final class CreateListeningQueryUseCase
{
    public function __construct(
        private readonly ListeningQueryRepositoryInterface $queryRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateListeningQueryInput $input): ListeningQueryOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $query = ListeningQuery::create(
            organizationId: $organizationId,
            name: $input->name,
            type: QueryType::from($input->type),
            value: $input->value,
            platforms: $input->platforms,
            userId: $input->userId,
        );

        $this->queryRepository->create($query);

        $this->eventDispatcher->dispatch(...$query->domainEvents);

        return ListeningQueryOutput::fromEntity($query);
    }
}
