<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class GetListeningDashboardInput
{
    public function __construct(
        public string $organizationId,
        public ?string $queryId = null,
        public string $period = '7d',
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
