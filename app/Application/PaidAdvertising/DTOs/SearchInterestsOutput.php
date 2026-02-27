<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class SearchInterestsOutput
{
    /**
     * @param  array<array{id: string, name: string, audience_size: ?int}>  $interests
     */
    public function __construct(
        public array $interests,
    ) {}
}
