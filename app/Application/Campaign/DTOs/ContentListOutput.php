<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class ContentListOutput
{
    /**
     * @param  ContentOutput[]  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
