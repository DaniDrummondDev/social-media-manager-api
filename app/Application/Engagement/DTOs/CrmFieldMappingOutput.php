<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\ValueObjects\CrmFieldMapping;

final readonly class CrmFieldMappingOutput
{
    public function __construct(
        public string $smmField,
        public string $crmField,
        public ?string $transform,
        public int $position,
    ) {}

    public static function fromValueObject(CrmFieldMapping $mapping): self
    {
        return new self(
            smmField: $mapping->smmField,
            crmField: $mapping->crmField,
            transform: $mapping->transform,
            position: $mapping->position,
        );
    }
}
