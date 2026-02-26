<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class CreateAlertInput
{
    /**
     * @param  array<string>  $queryIds
     * @param  array<string>  $channels
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public array $queryIds,
        public string $conditionType,
        public int $threshold,
        public int $windowMinutes,
        public array $channels,
        public int $cooldownMinutes,
    ) {}
}
