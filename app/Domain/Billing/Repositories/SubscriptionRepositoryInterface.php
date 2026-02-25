<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Entities\Subscription;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface SubscriptionRepositoryInterface
{
    public function findById(Uuid $id): ?Subscription;

    public function findActiveByOrganization(Uuid $organizationId): ?Subscription;

    public function findByExternalId(string $externalSubscriptionId): ?Subscription;

    public function create(Subscription $subscription): void;

    public function update(Subscription $subscription): void;

    /**
     * @return array<Subscription>
     */
    public function findExpiredPastDue(DateTimeImmutable $threshold): array;

    /**
     * @return array<Subscription>
     */
    public function findCanceledEndingBefore(DateTimeImmutable $date): array;
}
