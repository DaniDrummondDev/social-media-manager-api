<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class UpdateWebhookInput
{
    /**
     * @param  array<string>|null  $events
     * @param  array<string, string>|null  $headers
     */
    public function __construct(
        public string $organizationId,
        public string $webhookId,
        public ?string $name = null,
        public ?string $url = null,
        public ?array $events = null,
        public ?array $headers = null,
        public ?bool $isActive = null,
    ) {}
}
