<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class MediaListOutput
{
    /**
     * @param  MediaOutput[]  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
