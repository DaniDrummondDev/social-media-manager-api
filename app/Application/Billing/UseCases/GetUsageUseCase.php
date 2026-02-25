<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\UsageOutput;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GetUsageUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly UsageRecordRepositoryInterface $usageRecordRepository,
    ) {}

    public function execute(string $organizationId): UsageOutput
    {
        $orgId = Uuid::fromString($organizationId);

        $subscription = $this->subscriptionRepository->findActiveByOrganization($orgId);
        if ($subscription === null) {
            throw new SubscriptionNotFoundException;
        }

        $plan = $this->planRepository->findById($subscription->planId);
        if ($plan === null) {
            throw new PlanNotFoundException;
        }

        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $records = $this->usageRecordRepository->findAllByOrganizationForPeriod($orgId, $periodStart);

        $usageByType = [];
        foreach ($records as $record) {
            $usageByType[$record->resourceType->value] = $record->quantity;
        }

        $usage = [];
        foreach (UsageResourceType::cases() as $resource) {
            $used = $usageByType[$resource->value] ?? 0;
            $limit = $plan->limits->getLimit($resource);
            $isUnlimited = $plan->limits->isUnlimited($resource);

            $usage[$resource->value] = [
                'used' => $used,
                'limit' => $limit,
                'percentage' => $isUnlimited ? null : ($limit > 0 ? round(($used / $limit) * 100, 1) : 100.0),
            ];
        }

        return new UsageOutput(
            plan: $plan->name,
            billingCycle: $subscription->billingCycle->value,
            currentPeriodEnd: $subscription->currentPeriodEnd->format('c'),
            usage: $usage,
        );
    }
}
