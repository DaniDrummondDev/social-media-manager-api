<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

use App\Domain\SocialListening\Entities\ListeningAlert;

final readonly class ListeningAlertOutput
{
    /**
     * @param  array<string>  $queryIds
     * @param  array<string>  $channels
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public array $queryIds,
        public string $conditionType,
        public int $threshold,
        public int $windowMinutes,
        public array $channels,
        public int $cooldownMinutes,
        public bool $isActive,
        public ?string $lastTriggeredAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ListeningAlert $alert): self
    {
        return new self(
            id: (string) $alert->id,
            organizationId: (string) $alert->organizationId,
            name: $alert->name,
            queryIds: $alert->queryIds,
            conditionType: $alert->condition->type->value,
            threshold: $alert->condition->threshold,
            windowMinutes: $alert->condition->windowMinutes,
            channels: array_map(fn ($ch) => $ch->value, $alert->channels),
            cooldownMinutes: $alert->cooldownMinutes,
            isActive: $alert->isActive,
            lastTriggeredAt: $alert->lastTriggeredAt?->format('c'),
            createdAt: $alert->createdAt->format('c'),
            updatedAt: $alert->updatedAt->format('c'),
        );
    }
}
