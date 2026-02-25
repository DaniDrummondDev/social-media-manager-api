<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Resources;

use App\Application\Engagement\DTOs\BlacklistWordOutput;

final readonly class BlacklistWordResource
{
    private function __construct(
        private BlacklistWordOutput $output,
    ) {}

    public static function fromOutput(BlacklistWordOutput $output): self
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
            'type' => 'blacklist_word',
            'attributes' => [
                'organization_id' => $this->output->organizationId,
                'word' => $this->output->word,
                'is_regex' => $this->output->isRegex,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
