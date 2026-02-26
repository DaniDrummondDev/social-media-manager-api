<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\Shared\ValueObjects\Uuid;

interface ContentProfileRepositoryInterface
{
    public function create(ContentProfile $profile): void;

    public function update(ContentProfile $profile): void;

    public function findById(Uuid $id): ?ContentProfile;

    public function findByOrganization(
        Uuid $organizationId,
        ?string $provider = null,
        ?Uuid $socialAccountId = null,
    ): ?ContentProfile;
}
