<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class SuggestReplyOutput
{
    /**
     * @param  array<string>  $suggestions
     */
    public function __construct(
        public array $suggestions,
    ) {}
}
