<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\CheckPlanLimitInput;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class CheckPlanLimitUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly UsageRecordRepositoryInterface $usageRecordRepository,
    ) {}

    public function execute(CheckPlanLimitInput $input): bool
    {
        $orgId = Uuid::fromString($input->organizationId);
        $resourceType = UsageResourceType::from($input->resourceType);

        $subscription = $this->subscriptionRepository->findActiveByOrganization($orgId);
        if ($subscription === null) {
            return false;
        }

        $plan = $this->planRepository->findById($subscription->planId);
        if ($plan === null) {
            return false;
        }

        $limit = $plan->limits->getLimit($resourceType);

        if ($plan->limits->isUnlimited($resourceType)) {
            return true;
        }

        $periodStart = new DateTimeImmutable('first day of this month midnight');
        $record = $this->usageRecordRepository->findByOrganizationAndResource($orgId, $resourceType, $periodStart);
        $used = $record !== null ? $record->quantity : 0;

        return $used < $limit;
    }
}
