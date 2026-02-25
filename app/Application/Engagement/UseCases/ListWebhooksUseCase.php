<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\WebhookOutput;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListWebhooksUseCase
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $webhookRepository,
    ) {}

    /**
     * @return array<WebhookOutput>
     */
    public function execute(string $organizationId): array
    {
        $orgId = Uuid::fromString($organizationId);
        $endpoints = $this->webhookRepository->findByOrganizationId($orgId);

        return array_map(
            fn ($endpoint) => WebhookOutput::fromEntity($endpoint),
            $endpoints,
        );
    }
}
