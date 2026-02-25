<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ListDeliveriesInput
{
    public function __construct(
        public string $organizationId,
        public string $webhookId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
