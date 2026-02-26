<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\CrmFieldMappingOutput;

final readonly class CrmFieldMappingResource
{
    private function __construct(
        private CrmFieldMappingOutput $output,
    ) {}

    public static function fromOutput(CrmFieldMappingOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'smm_field' => $this->output->smmField,
            'crm_field' => $this->output->crmField,
            'transform' => $this->output->transform,
            'position' => $this->output->position,
        ];
    }
}
