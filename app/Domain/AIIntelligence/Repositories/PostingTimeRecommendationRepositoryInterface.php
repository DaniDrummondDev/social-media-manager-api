<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;
use App\Domain\Shared\ValueObjects\Uuid;

interface PostingTimeRecommendationRepositoryInterface
{
    public function create(PostingTimeRecommendation $recommendation): void;

    public function update(PostingTimeRecommendation $recommendation): void;

    public function findById(Uuid $id): ?PostingTimeRecommendation;

    public function findByOrganization(
        Uuid $organizationId,
        ?string $provider = null,
        ?Uuid $socialAccountId = null,
    ): ?PostingTimeRecommendation;

    public function deleteByOrganization(Uuid $organizationId, ?string $provider = null): void;
}
