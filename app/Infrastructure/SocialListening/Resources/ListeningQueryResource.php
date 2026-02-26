<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Resources;

use App\Application\SocialListening\DTOs\ListeningQueryOutput;

final readonly class ListeningQueryResource
{
    public function __construct(
        private ListeningQueryOutput $output,
    ) {}

    public static function fromOutput(ListeningQueryOutput $output): self
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
            'type' => 'listening_query',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'name' => $this->output->name,
                'type' => $this->output->type,
                'value' => $this->output->value,
                'platforms' => $this->output->platforms,
                'is_active' => $this->output->isActive,
                'created_at' => $this->output->createdAt,
                'updated_at' => $this->output->updatedAt,
            ],
        ];
    }
}
