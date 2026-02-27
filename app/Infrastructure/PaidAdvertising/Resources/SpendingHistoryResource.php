<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Resources;

use App\Application\PaidAdvertising\DTOs\SpendingHistoryOutput;

final readonly class SpendingHistoryResource
{
    private function __construct(
        private SpendingHistoryOutput $output,
    ) {}

    public static function fromOutput(SpendingHistoryOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => null,
            'type' => 'spending_history',
            'attributes' => [
                'history' => $this->output->history,
            ],
        ];
    }
}
