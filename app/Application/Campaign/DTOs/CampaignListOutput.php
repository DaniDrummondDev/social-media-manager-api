<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class CampaignListOutput
{
    /**
     * @param  CampaignOutput[]  $items
     */
    public function __construct(
        public array $items,
    ) {}
}
