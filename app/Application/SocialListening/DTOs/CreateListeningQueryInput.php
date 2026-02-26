<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class CreateListeningQueryInput
{
    /**
     * @param  array<string>  $platforms
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public string $type,
        public string $value,
        public array $platforms,
    ) {}
}
