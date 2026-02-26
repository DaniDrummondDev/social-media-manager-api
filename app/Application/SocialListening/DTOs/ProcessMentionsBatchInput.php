<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class ProcessMentionsBatchInput
{
    /**
     * @param  array<array<string, mixed>>  $mentionsData
     */
    public function __construct(
        public string $organizationId,
        public string $queryId,
        public array $mentionsData,
    ) {}
}
