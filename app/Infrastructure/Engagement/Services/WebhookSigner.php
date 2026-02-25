<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Domain\Engagement\ValueObjects\WebhookSecret;

final readonly class WebhookSigner
{
    public function sign(WebhookSecret $secret, string $payload, int $timestamp): string
    {
        return $secret->sign($payload, $timestamp);
    }

    /**
     * @return array<string, string>
     */
    public function buildHeaders(WebhookSecret $secret, string $payload, string $event, string $deliveryId): array
    {
        $timestamp = time();
        $signature = $this->sign($secret, $payload, $timestamp);

        return [
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Event' => $event,
            'X-Webhook-Delivery-Id' => $deliveryId,
        ];
    }
}
