<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Contracts;

use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Shared\ValueObjects\Uuid;

interface ContentNetworkOverrideRepositoryInterface
{
    /**
     * @param  ContentNetworkOverride[]  $overrides
     */
    public function createMany(array $overrides): void;

    /**
     * @return ContentNetworkOverride[]
     */
    public function findByContentId(Uuid $contentId): array;

    public function deleteByContentId(Uuid $contentId): void;

    /**
     * @param  ContentNetworkOverride[]  $overrides
     */
    public function replaceForContent(Uuid $contentId, array $overrides): void;
}
