<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Listeners;

use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Engagement\Events\CommentReplied;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Jobs\DeliverWebhookJob;

final class DispatchWebhooksOnCommentReplied
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $endpointRepository,
        private readonly WebhookDeliveryRepositoryInterface $deliveryRepository,
    ) {}

    public function handle(CommentReplied $event): void
    {
        $organizationId = Uuid::fromString($event->organizationId);
        $endpoints = $this->endpointRepository->findSubscribedToEvent($organizationId, 'comment.replied');

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create(
                webhookEndpointId: $endpoint->id,
                event: 'comment.replied',
                payload: [
                    'event' => 'comment.replied',
                    'comment_id' => $event->aggregateId,
                    'replied_by' => $event->repliedBy,
                    'organization_id' => $event->organizationId,
                    'occurred_at' => $event->occurredAt->format('c'),
                ],
            );

            $this->deliveryRepository->create($delivery);
            DeliverWebhookJob::dispatch((string) $delivery->id);
        }
    }
}
