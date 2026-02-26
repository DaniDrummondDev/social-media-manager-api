<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class UpdateAlertInput
{
    /**
     * @param  array<string>|null  $queryIds
     * @param  array<string>|null  $channels
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $alertId,
        public ?string $name = null,
        public ?array $queryIds = null,
        public ?string $conditionType = null,
        public ?int $threshold = null,
        public ?int $windowMinutes = null,
        public ?array $channels = null,
        public ?int $cooldownMinutes = null,
    ) {}
}
