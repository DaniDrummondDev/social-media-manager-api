<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface WebhookDeliveryRepositoryInterface
{
    public function create(WebhookDelivery $delivery): void;

    public function update(WebhookDelivery $delivery): void;

    public function findById(Uuid $id): ?WebhookDelivery;

    /**
     * @return array<WebhookDelivery>
     */
    public function findByEndpointId(Uuid $endpointId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<WebhookDelivery>
     */
    public function findPendingRetries(DateTimeImmutable $now): array;
}
