<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\CreatePortalSessionInput;
use App\Application\Billing\DTOs\PortalSessionOutput;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreatePortalSessionUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function execute(CreatePortalSessionInput $input): PortalSessionOutput
    {
        $subscription = $this->subscriptionRepository->findActiveByOrganization(
            Uuid::fromString($input->organizationId),
        );

        if ($subscription === null || $subscription->externalCustomerId === null) {
            throw new SubscriptionNotFoundException('Assinatura sem integração com gateway de pagamento.');
        }

        $session = $this->paymentGateway->createPortalSession(
            customerId: $subscription->externalCustomerId,
            returnUrl: $input->returnUrl,
        );

        return new PortalSessionOutput(
            portalUrl: $session['portal_url'],
        );
    }
}
