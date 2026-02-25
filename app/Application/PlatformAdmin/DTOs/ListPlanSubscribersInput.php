<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class ListPlanSubscribersInput
{
    public function __construct(
        public string $planId,
        public ?string $subscriptionStatus = null,
        public string $sort = '-created_at',
        public int $perPage = 20,
        public ?string $cursor = null,
    ) {}
}
