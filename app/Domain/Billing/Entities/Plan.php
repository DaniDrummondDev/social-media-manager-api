<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entities;

use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Plan
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public Money $priceMonthly,
        public Money $priceYearly,
        public PlanLimits $limits,
        public PlanFeatures $features,
        public bool $isActive,
        public int $sortOrder,
        public ?string $stripePriceMonthlyId,
        public ?string $stripePriceYearlyId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function reconstitute(
        Uuid $id,
        string $name,
        string $slug,
        ?string $description,
        Money $priceMonthly,
        Money $priceYearly,
        PlanLimits $limits,
        PlanFeatures $features,
        bool $isActive,
        int $sortOrder,
        ?string $stripePriceMonthlyId,
        ?string $stripePriceYearlyId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            priceMonthly: $priceMonthly,
            priceYearly: $priceYearly,
            limits: $limits,
            features: $features,
            isActive: $isActive,
            sortOrder: $sortOrder,
            stripePriceMonthlyId: $stripePriceMonthlyId,
            stripePriceYearlyId: $stripePriceYearlyId,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function isFree(): bool
    {
        return $this->priceMonthly->isZero() && $this->priceYearly->isZero();
    }

    public function getStripePriceId(BillingCycle $cycle): ?string
    {
        return match ($cycle) {
            BillingCycle::Monthly => $this->stripePriceMonthlyId,
            BillingCycle::Yearly => $this->stripePriceYearlyId,
        };
    }
}
