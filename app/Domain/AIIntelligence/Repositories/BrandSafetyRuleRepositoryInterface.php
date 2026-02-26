<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\BrandSafetyRule;
use App\Domain\Shared\ValueObjects\Uuid;

interface BrandSafetyRuleRepositoryInterface
{
    public function create(BrandSafetyRule $rule): void;

    public function update(BrandSafetyRule $rule): void;

    public function findById(Uuid $id): ?BrandSafetyRule;

    /**
     * @return array{items: array<BrandSafetyRule>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<BrandSafetyRule>
     */
    public function findActiveByOrganizationId(Uuid $organizationId): array;

    public function delete(Uuid $id): void;
}
