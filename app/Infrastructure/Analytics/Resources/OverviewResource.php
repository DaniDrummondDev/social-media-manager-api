<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Resources;

use App\Application\Analytics\DTOs\GetOverviewOutput;

final readonly class OverviewResource
{
    private function __construct(
        private GetOverviewOutput $output,
    ) {}

    public static function fromOutput(GetOverviewOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'period' => $this->output->period,
            'summary' => $this->output->summary,
            'comparison' => $this->output->comparison,
            'by_network' => $this->output->byNetwork,
            'trend' => $this->output->trend,
            'top_contents' => $this->output->topContents,
        ];
    }
}
