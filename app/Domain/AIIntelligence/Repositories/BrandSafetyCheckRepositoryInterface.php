<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;
use App\Domain\Shared\ValueObjects\Uuid;

interface BrandSafetyCheckRepositoryInterface
{
    public function create(BrandSafetyCheck $check): void;

    public function update(BrandSafetyCheck $check): void;

    public function findById(Uuid $id): ?BrandSafetyCheck;

    /**
     * @return array<BrandSafetyCheck>
     */
    public function findByContentId(Uuid $contentId): array;

    /**
     * @return array{items: array<BrandSafetyCheck>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;
}
