<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Resources;

use App\Application\Identity\DTOs\MessageOutput;

final readonly class MessageResource
{
    private function __construct(
        private string $message,
    ) {}

    public static function fromOutput(MessageOutput $output): self
    {
        return new self(
            message: $output->message,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
