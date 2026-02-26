<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

final readonly class SafetyCheckResult
{
    private function __construct(
        public SafetyCategory $category,
        public SafetyStatus $status,
        public ?string $message,
        public ?RuleSeverity $severity,
    ) {}

    public static function create(
        SafetyCategory $category,
        SafetyStatus $status,
        ?string $message = null,
        ?RuleSeverity $severity = null,
    ): self {
        return new self($category, $status, $message, $severity);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            category: SafetyCategory::from($data['category']),
            status: SafetyStatus::from($data['status']),
            message: $data['message'] ?? null,
            severity: isset($data['severity']) ? RuleSeverity::from($data['severity']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'status' => $this->status->value,
            'message' => $this->message,
            'severity' => $this->severity?->value,
        ];
    }
}
