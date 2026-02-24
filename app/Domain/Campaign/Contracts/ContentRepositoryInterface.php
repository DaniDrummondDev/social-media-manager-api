<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Contracts;

use App\Domain\Campaign\Entities\Content;
use App\Domain\Shared\ValueObjects\Uuid;

interface ContentRepositoryInterface
{
    public function create(Content $content): void;

    public function update(Content $content): void;

    public function findById(Uuid $id): ?Content;

    /**
     * @return Content[]
     */
    public function findByCampaignId(Uuid $campaignId): array;

    public function delete(Uuid $id): void;

    /**
     * @return array<string, int>
     */
    public function countByCampaignAndStatus(Uuid $campaignId): array;
}
