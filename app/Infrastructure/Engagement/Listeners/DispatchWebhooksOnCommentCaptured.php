<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Listeners;

use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Engagement\Events\CommentCaptured;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Jobs\DeliverWebhookJob;

final class DispatchWebhooksOnCommentCaptured
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $endpointRepository,
        private readonly WebhookDeliveryRepositoryInterface $deliveryRepository,
    ) {}

    public function handle(CommentCaptured $event): void
    {
        $organizationId = Uuid::fromString($event->organizationId);
        $endpoints = $this->endpointRepository->findSubscribedToEvent($organizationId, 'comment.created');

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create(
                webhookEndpointId: $endpoint->id,
                event: 'comment.created',
                payload: [
                    'event' => 'comment.created',
                    'comment_id' => $event->aggregateId,
                    'content_id' => $event->contentId,
                    'provider' => $event->provider,
                    'organization_id' => $event->organizationId,
                    'occurred_at' => $event->occurredAt->format('c'),
                ],
            );

            $this->deliveryRepository->create($delivery);
            DeliverWebhookJob::dispatch((string) $delivery->id);
        }
    }
}
