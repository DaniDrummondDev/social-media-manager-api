<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Resources;

use App\Application\AIIntelligence\DTOs\ContentThemesOutput;

final readonly class ContentThemesResource
{
    private function __construct(private ContentThemesOutput $output) {}

    public static function fromOutput(ContentThemesOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'themes' => $this->output->themes,
        ];
    }
}
