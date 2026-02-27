<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\BoostMetricsOutput;

final readonly class BoostMetricsResource
{
    private function __construct(
        private BoostMetricsOutput $output,
    ) {}

    public static function fromOutput(BoostMetricsOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->boostId,
            'type' => 'boost_metrics',
            'attributes' => [
                'boost_id' => $this->output->boostId,
                'snapshots' => $this->output->snapshots,
                'summary' => $this->output->summary,
            ],
        ];
    }
}
