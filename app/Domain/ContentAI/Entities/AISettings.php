<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Entities;

use App\Domain\ContentAI\Events\AISettingsUpdated;
use App\Domain\ContentAI\ValueObjects\Language;
use App\Domain\ContentAI\ValueObjects\Tone;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AISettings
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $organizationId,
        public Tone $defaultTone,
        public ?string $customToneDescription,
        public Language $defaultLanguage,
        public int $monthlyGenerationLimit,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Tone $defaultTone = Tone::Professional,
        ?string $customToneDescription = null,
        Language $defaultLanguage = Language::PtBR,
        int $monthlyGenerationLimit = 500,
    ): self {
        self::validateCustomTone($defaultTone, $customToneDescription);

        $now = new DateTimeImmutable;

        return new self(
            organizationId: $organizationId,
            defaultTone: $defaultTone,
            customToneDescription: $customToneDescription,
            defaultLanguage: $defaultLanguage,
            monthlyGenerationLimit: $monthlyGenerationLimit,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function reconstitute(
        Uuid $organizationId,
        Tone $defaultTone,
        ?string $customToneDescription,
        Language $defaultLanguage,
        int $monthlyGenerationLimit,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            organizationId: $organizationId,
            defaultTone: $defaultTone,
            customToneDescription: $customToneDescription,
            defaultLanguage: $defaultLanguage,
            monthlyGenerationLimit: $monthlyGenerationLimit,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function update(
        ?Tone $defaultTone = null,
        ?string $customToneDescription = null,
        ?Language $defaultLanguage = null,
    ): self {
        $tone = $defaultTone ?? $this->defaultTone;
        $toneDesc = $defaultTone !== null ? $customToneDescription : ($customToneDescription ?? $this->customToneDescription);
        self::validateCustomTone($tone, $toneDesc);

        $now = new DateTimeImmutable;

        return new self(
            organizationId: $this->organizationId,
            defaultTone: $tone,
            customToneDescription: $toneDesc,
            defaultLanguage: $defaultLanguage ?? $this->defaultLanguage,
            monthlyGenerationLimit: $this->monthlyGenerationLimit,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new AISettingsUpdated(
                    aggregateId: (string) $this->organizationId,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->organizationId,
                ),
            ],
        );
    }

    public function releaseEvents(): self
    {
        return new self(
            organizationId: $this->organizationId,
            defaultTone: $this->defaultTone,
            customToneDescription: $this->customToneDescription,
            defaultLanguage: $this->defaultLanguage,
            monthlyGenerationLimit: $this->monthlyGenerationLimit,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private static function validateCustomTone(Tone $tone, ?string $description): void
    {
        if ($tone === Tone::Custom && ($description === null || trim($description) === '')) {
            throw new InvalidArgumentException('Custom tone description is required when tone is "custom".');
        }
    }
}
