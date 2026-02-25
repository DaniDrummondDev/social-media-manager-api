<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\UpdateWebhookInput;
use App\Application\Engagement\DTOs\WebhookOutput;
use App\Application\Engagement\Exceptions\WebhookNotFoundException;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class UpdateWebhookUseCase
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $webhookRepository,
    ) {}

    public function execute(UpdateWebhookInput $input): WebhookOutput
    {
        $webhookId = Uuid::fromString($input->webhookId);
        $endpoint = $this->webhookRepository->findById($webhookId);

        if ($endpoint === null || (string) $endpoint->organizationId !== $input->organizationId) {
            throw new WebhookNotFoundException($input->webhookId);
        }

        $endpoint = $endpoint->update(
            name: $input->name,
            url: $input->url,
            events: $input->events,
            headers: $input->headers,
            isActive: $input->isActive,
        );

        $this->webhookRepository->update($endpoint);

        return WebhookOutput::fromEntity($endpoint);
    }
}
