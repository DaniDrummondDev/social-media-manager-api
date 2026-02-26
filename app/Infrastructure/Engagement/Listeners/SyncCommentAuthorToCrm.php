<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Listeners;

use App\Application\Engagement\DTOs\SyncContactToCrmInput;
use App\Domain\Engagement\Events\CommentCaptured;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Jobs\SyncContactToCrmJob;

final class SyncCommentAuthorToCrm
{
    public function __construct(
        private readonly CrmConnectionRepositoryInterface $connectionRepository,
    ) {}

    public function handle(CommentCaptured $event): void
    {
        $orgId = Uuid::fromString($event->organizationId);
        $connections = $this->connectionRepository->findByOrganizationId($orgId);

        foreach ($connections as $connection) {
            if (! $connection->canSync()) {
                continue;
            }

            SyncContactToCrmJob::dispatch(new SyncContactToCrmInput(
                organizationId: $event->organizationId,
                userId: $event->userId,
                connectionId: (string) $connection->id,
                authorName: $event->authorName ?? 'Unknown',
                authorExternalId: $event->authorExternalId ?? $event->aggregateId,
                network: $event->provider ?? null,
            ));
        }
    }
}
