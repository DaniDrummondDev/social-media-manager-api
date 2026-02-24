<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class UpdateAISettingsInput
{
    public function __construct(
        public string $organizationId,
        public ?string $defaultTone = null,
        public ?string $customToneDescription = null,
        public ?string $defaultLanguage = null,
    ) {}
}
