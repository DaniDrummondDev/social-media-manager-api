<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Contracts;

use App\Domain\Shared\ValueObjects\Uuid;

interface ContentMediaRepositoryInterface
{
    /**
     * @param  array<int, string>  $mediaIds  Ordered array of media UUIDs (index = position)
     */
    public function sync(Uuid $contentId, array $mediaIds): void;

    /**
     * @return array<int, array{media_id: string, position: int}>
     */
    public function findByContentId(Uuid $contentId): array;
}
