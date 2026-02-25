<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use App\Application\Engagement\DTOs\TestWebhookInput;
use App\Application\Engagement\DTOs\TestWebhookOutput;
use App\Application\Engagement\Exceptions\WebhookNotFoundException;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class TestWebhookUseCase
{
    public function __construct(
        private readonly WebhookEndpointRepositoryInterface $webhookRepository,
        private readonly WebhookHttpClientInterface $httpClient,
    ) {}

    public function execute(TestWebhookInput $input): TestWebhookOutput
    {
        $webhookId = Uuid::fromString($input->webhookId);
        $endpoint = $this->webhookRepository->findById($webhookId);

        if ($endpoint === null || (string) $endpoint->organizationId !== $input->organizationId) {
            throw new WebhookNotFoundException($input->webhookId);
        }

        $timestamp = time();
        $payload = json_encode([
            'event' => 'webhook.test',
            'timestamp' => $timestamp,
            'data' => ['message' => 'This is a test webhook delivery.'],
        ], JSON_THROW_ON_ERROR);

        $signature = $endpoint->secret->sign($payload, $timestamp);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Event' => 'webhook.test',
        ];

        if ($endpoint->headers !== null) {
            $headers = array_merge($headers, $endpoint->headers);
        }

        try {
            $result = $this->httpClient->post($endpoint->url, $headers, $payload);

            return new TestWebhookOutput(
                success: $result['status'] >= 200 && $result['status'] < 300,
                responseStatus: $result['status'],
                responseTimeMs: $result['time_ms'],
            );
        } catch (\Throwable) {
            return new TestWebhookOutput(
                success: false,
                responseStatus: null,
                responseTimeMs: 0,
            );
        }
    }
}
