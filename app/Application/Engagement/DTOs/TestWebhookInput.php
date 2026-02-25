<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class TestWebhookInput
{
    public function __construct(
        public string $organizationId,
        public string $webhookId,
    ) {}
}
