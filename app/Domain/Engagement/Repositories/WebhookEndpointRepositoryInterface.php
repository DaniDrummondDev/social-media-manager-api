<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Shared\ValueObjects\Uuid;

interface WebhookEndpointRepositoryInterface
{
    public function create(WebhookEndpoint $endpoint): void;

    public function update(WebhookEndpoint $endpoint): void;

    public function findById(Uuid $id): ?WebhookEndpoint;

    /**
     * @return array<WebhookEndpoint>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    /**
     * @return array<WebhookEndpoint>
     */
    public function findSubscribedToEvent(Uuid $organizationId, string $event): array;

    public function countByOrganization(Uuid $organizationId): int;

    public function delete(Uuid $id): void;
}
