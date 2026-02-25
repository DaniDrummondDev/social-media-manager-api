<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Exceptions\WebhookNotFoundException;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeleteWebhookUseCase
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $webhookRepository,
    ) {}

    public function execute(string $organizationId, string $webhookId): void
    {
        $id = Uuid::fromString($webhookId);
        $endpoint = $this->webhookRepository->findById($id);

        if ($endpoint === null || (string) $endpoint->organizationId !== $organizationId) {
            throw new WebhookNotFoundException($webhookId);
        }

        $endpoint = $endpoint->softDelete();
        $this->webhookRepository->update($endpoint);
    }
}
