<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class CreateWebhookInput
{
    /**
     * @param  array<string>  $events
     * @param  array<string, string>|null  $headers
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public string $url,
        public array $events,
        public ?array $headers = null,
    ) {}
}
