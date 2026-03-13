<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

interface PaymentGatewayInterface
{
    /**
     * @param  array<string, string>  $metadata
     * @return array{checkout_url: string, session_id: string, expires_at: string}
     */
    public function createCheckoutSession(
        string $customerId,
        string $priceId,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        ?int $trialPeriodDays = null,
    ): array;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createCustomer(
        string $email,
        string $name,
        array $metadata = [],
    ): string;

    /**
     * @return array{portal_url: string}
     */
    public function createPortalSession(
        string $customerId,
        string $returnUrl,
    ): array;

    public function cancelSubscription(
        string $subscriptionId,
        bool $atPeriodEnd = true,
    ): void;

    public function reactivateSubscription(
        string $subscriptionId,
    ): void;

    public function validateWebhookSignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool;

    /**
     * @return array<string, mixed>
     */
    public function constructWebhookEvent(
        string $payload,
        string $signature,
        string $secret,
    ): array;
}
