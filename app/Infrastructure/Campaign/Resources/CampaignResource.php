<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Resources;

use App\Application\Campaign\DTOs\CampaignOutput;

final readonly class CampaignResource
{
    /**
     * @param  string[]  $tags
     * @param  array<string, int>|null  $stats
     */
    private function __construct(
        private string $id,
        private string $name,
        private ?string $description,
        private ?string $startsAt,
        private ?string $endsAt,
        private string $status,
        private array $tags,
        private ?array $stats,
        private string $createdAt,
        private string $updatedAt,
    ) {}

    public static function fromOutput(CampaignOutput $output): self
    {
        return new self(
            id: $output->id,
            name: $output->name,
            description: $output->description,
            startsAt: $output->startsAt,
            endsAt: $output->endsAt,
            status: $output->status,
            tags: $output->tags,
            stats: $output->stats,
            createdAt: $output->createdAt,
            updatedAt: $output->updatedAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'campaign',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
                'status' => $this->status,
                'tags' => $this->tags,
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
        ];

        if ($this->stats !== null) {
            $data['attributes']['stats'] = $this->stats;
        }

        return $data;
    }
}
