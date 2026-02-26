<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class GenerateListeningReportInput
{
    /**
     * @param  array<string>  $queryIds
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public array $queryIds,
        public string $periodFrom,
        public string $periodTo,
    ) {}
}
