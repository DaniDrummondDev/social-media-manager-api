<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

use App\Domain\SocialListening\Entities\ListeningQuery;

final readonly class ListeningQueryOutput
{
    /**
     * @param  array<string>  $platforms
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public string $type,
        public string $value,
        public array $platforms,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(ListeningQuery $query): self
    {
        return new self(
            id: (string) $query->id,
            organizationId: (string) $query->organizationId,
            name: $query->name,
            type: $query->type->value,
            value: $query->value,
            platforms: $query->platforms,
            isActive: $query->isActive(),
            createdAt: $query->createdAt->format('c'),
            updatedAt: $query->updatedAt->format('c'),
        );
    }
}
