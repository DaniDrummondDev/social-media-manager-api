<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\ListDeliveriesInput;
use App\Application\Engagement\DTOs\WebhookDeliveryOutput;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListDeliveriesUseCase
{
    public function __construct(
        private readonly WebhookDeliveryRepositoryInterface $deliveryRepository,
    ) {}

    /**
     * @return array<WebhookDeliveryOutput>
     */
    public function execute(ListDeliveriesInput $input): array
    {
        $endpointId = Uuid::fromString($input->webhookId);

        $deliveries = $this->deliveryRepository->findByEndpointId(
            $endpointId,
            $input->cursor,
            $input->limit,
        );

        return array_map(
            fn ($delivery) => WebhookDeliveryOutput::fromEntity($delivery),
            $deliveries,
        );
    }
}
