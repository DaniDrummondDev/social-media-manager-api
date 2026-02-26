<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class ListListeningQueriesInput
{
    public function __construct(
        public string $organizationId,
        public ?string $status = null,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {}
}
