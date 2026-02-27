<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\AudienceOutput;

final readonly class AudienceResource
{
    private function __construct(
        private AudienceOutput $output,
    ) {}

    public static function fromOutput(AudienceOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'audience',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'name' => $this->output->name,
                'targeting_spec' => $this->output->targetingSpec,
                'provider_audience_ids' => $this->output->providerAudienceIds,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
