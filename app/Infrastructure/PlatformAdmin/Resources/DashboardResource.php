<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\DashboardOutput;

final readonly class DashboardResource
{
    private function __construct(
        private DashboardOutput $output,
    ) {}

    public static function fromOutput(DashboardOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'platform_dashboard',
            'attributes' => [
                'overview' => $this->output->overview,
                'subscriptions' => $this->output->subscriptions,
                'usage' => $this->output->usage,
                'health' => $this->output->health,
                'generated_at' => $this->output->generatedAt,
            ],
        ];
    }
}
