<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\CreateWebhookInput;
use App\Application\Engagement\DTOs\WebhookOutput;
use App\Application\Engagement\Exceptions\WebhookLimitExceededException;
use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateWebhookUseCase
{
    private const int MAX_WEBHOOKS_PER_ORG = 10;

    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $webhookRepository,
    ) {}

    public function execute(CreateWebhookInput $input): WebhookOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $count = $this->webhookRepository->countByOrganization($organizationId);
        if ($count >= self::MAX_WEBHOOKS_PER_ORG) {
            throw new WebhookLimitExceededException(self::MAX_WEBHOOKS_PER_ORG);
        }

        $endpoint = WebhookEndpoint::create(
            organizationId: $organizationId,
            name: $input->name,
            url: $input->url,
            events: $input->events,
            headers: $input->headers,
            userId: $input->userId,
        );

        $this->webhookRepository->create($endpoint);

        return WebhookOutput::fromEntity($endpoint, includeSecret: true);
    }
}
