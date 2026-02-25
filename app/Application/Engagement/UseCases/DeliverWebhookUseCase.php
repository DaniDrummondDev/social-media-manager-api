<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DeliverWebhookUseCase
{
    public function __construct(
        private readonly WebhookDeliveryRepositoryInterface $deliveryRepository,
        private readonly WebhookEndpointRepositoryInterface $endpointRepository,
        private readonly WebhookHttpClientInterface $httpClient,
    ) {}

    public function execute(string $deliveryId): void
    {
        $id = Uuid::fromString($deliveryId);
        $delivery = $this->deliveryRepository->findById($id);

        if ($delivery === null) {
            return;
        }

        $endpoint = $this->endpointRepository->findById($delivery->webhookEndpointId);

        if ($endpoint === null || ! $endpoint->isActive) {
            return;
        }

        $timestamp = time();
        $payload = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = $endpoint->secret->sign($payload, $timestamp);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Delivery-Id' => (string) $delivery->id,
        ];

        if ($endpoint->headers !== null) {
            $headers = array_merge($headers, $endpoint->headers);
        }

        try {
            $result = $this->httpClient->post($endpoint->url, $headers, $payload);

            $status = $result['status'];
            $body = $result['body'];
            $elapsed = $result['time_ms'];

            if ($status >= 200 && $status < 300) {
                $delivery = $delivery->markAsDelivered($status, $body, $elapsed);
            } else {
                $delivery = $delivery->markAsFailed($status, $body, $elapsed);
            }

            $endpoint = $endpoint->recordDelivery($status);
        } catch (\Throwable $e) {
            $delivery = $delivery->markAsFailed(0, $e->getMessage(), 0);
            $endpoint = $endpoint->recordDelivery(0);
        }

        $this->deliveryRepository->update($delivery);
        $this->endpointRepository->update($endpoint);

        if ($endpoint->shouldAutoDeactivate()) {
            $endpoint = $endpoint->deactivate();
            $this->endpointRepository->update($endpoint);
        }
    }
}
