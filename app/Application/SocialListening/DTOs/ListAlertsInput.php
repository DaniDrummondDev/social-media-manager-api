<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class ListAlertsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
