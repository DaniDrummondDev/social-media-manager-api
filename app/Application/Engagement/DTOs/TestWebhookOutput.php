<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class TestWebhookOutput
{
    public function __construct(
        public bool $success,
        public ?int $responseStatus,
        public ?int $responseTimeMs,
    ) {}
}
