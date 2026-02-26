<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

final readonly class CrmFieldMapping
{
    public function __construct(
        public string $smmField,
        public string $crmField,
        public ?string $transform,
        public int $position = 0,
    ) {}

    public static function create(
        string $smmField,
        string $crmField,
        ?string $transform = null,
        int $position = 0,
    ): self {
        return new self(
            smmField: $smmField,
            crmField: $crmField,
            transform: $transform,
            position: $position,
        );
    }

    public function hasTransform(): bool
    {
        return $this->transform !== null && $this->transform !== '';
    }

    public function equals(self $other): bool
    {
        return $this->smmField === $other->smmField
            && $this->crmField === $other->crmField
            && $this->transform === $other->transform;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'smm_field' => $this->smmField,
            'crm_field' => $this->crmField,
            'transform' => $this->transform,
            'position' => $this->position,
        ];
    }
}
