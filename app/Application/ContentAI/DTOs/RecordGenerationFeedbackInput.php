<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class RecordGenerationFeedbackInput
{
    /**
     * @param  array<string, mixed>  $originalOutput
     * @param  array<string, mixed>|null  $editedOutput
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $generationId,
        public string $action,
        public array $originalOutput,
        public ?array $editedOutput = null,
        public ?string $contentId = null,
        public string $generationType = '',
        public ?int $timeToDecisionMs = null,
    ) {}
}
