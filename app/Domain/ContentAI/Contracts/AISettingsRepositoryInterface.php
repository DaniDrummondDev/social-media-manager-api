<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Contracts;

use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\Shared\ValueObjects\Uuid;

interface AISettingsRepositoryInterface
{
    public function findByOrganizationId(Uuid $organizationId): ?AISettings;

    public function upsert(AISettings $settings): void;
}
