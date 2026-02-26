<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class UpdateCrmFieldMappingsInput
{
    /**
     * @param  array<int, array{smm_field: string, crm_field: string, transform?: ?string}>  $mappings
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $connectionId,
        public array $mappings,
    ) {}
}
