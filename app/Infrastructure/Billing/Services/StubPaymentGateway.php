<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Services;

use App\Domain\Billing\Contracts\PaymentGatewayInterface;

final class StubPaymentGateway implements PaymentGatewayInterface
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
    ): array {
        throw new \RuntimeException('Stripe integration not yet implemented. Use stub data for testing.');
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function createCustomer(
        string $email,
        string $name,
        array $metadata = [],
    ): string {
        throw new \RuntimeException('Stripe integration not yet implemented.');
    }

    /**
     * @return array{portal_url: string}
     */
    public function createPortalSession(
        string $customerId,
        string $returnUrl,
    ): array {
        throw new \RuntimeException('Stripe integration not yet implemented.');
    }

    public function cancelSubscription(
        string $subscriptionId,
        bool $atPeriodEnd = true,
    ): void {
        throw new \RuntimeException('Stripe integration not yet implemented.');
    }

    public function reactivateSubscription(
        string $subscriptionId,
    ): void {
        throw new \RuntimeException('Stripe integration not yet implemented.');
    }

    public function validateWebhookSignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function constructWebhookEvent(
        string $payload,
        string $signature,
        string $secret,
    ): array {
        return json_decode($payload, true) ?? [];
    }
}
