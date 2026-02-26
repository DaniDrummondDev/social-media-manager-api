<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Entities;

use App\Domain\AIIntelligence\Exceptions\InvalidSafetyRuleConfigException;
use App\Domain\AIIntelligence\ValueObjects\RuleSeverity;
use App\Domain\AIIntelligence\ValueObjects\SafetyRuleType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class BrandSafetyRule
{
    /**
     * @param  array<string, mixed>  $ruleConfig
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public SafetyRuleType $ruleType,
        public array $ruleConfig,
        public RuleSeverity $severity,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $ruleConfig
     */
    public static function create(
        Uuid $organizationId,
        SafetyRuleType $ruleType,
        array $ruleConfig,
        RuleSeverity $severity,
    ): self {
        self::validateConfig($ruleType, $ruleConfig);

        $now = new DateTimeImmutable;

        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            ruleType: $ruleType,
            ruleConfig: $ruleConfig,
            severity: $severity,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param  array<string, mixed>  $ruleConfig
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        SafetyRuleType $ruleType,
        array $ruleConfig,
        RuleSeverity $severity,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            ruleType: $ruleType,
            ruleConfig: $ruleConfig,
            severity: $severity,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<string, mixed>|null  $ruleConfig
     */
    public function update(
        ?SafetyRuleType $ruleType = null,
        ?array $ruleConfig = null,
        ?RuleSeverity $severity = null,
    ): self {
        $newType = $ruleType ?? $this->ruleType;
        $newConfig = $ruleConfig ?? $this->ruleConfig;

        if ($ruleType !== null || $ruleConfig !== null) {
            self::validateConfig($newType, $newConfig);
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            ruleType: $newType,
            ruleConfig: $newConfig,
            severity: $severity ?? $this->severity,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            ruleType: $this->ruleType,
            ruleConfig: $this->ruleConfig,
            severity: $this->severity,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            ruleType: $this->ruleType,
            ruleConfig: $this->ruleConfig,
            severity: $this->severity,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function matches(string $content): bool
    {
        return match ($this->ruleType) {
            SafetyRuleType::BlockedWord => $this->matchesBlockedWord($content),
            SafetyRuleType::RequiredDisclosure => $this->matchesRequiredDisclosure($content),
            SafetyRuleType::CustomCheck => false,
        };
    }

    private function matchesBlockedWord(string $content): bool
    {
        /** @var array<string> $words */
        $words = $this->ruleConfig['words'] ?? [];
        $lowerContent = mb_strtolower($content);

        foreach ($words as $word) {
            if (str_contains($lowerContent, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    private function matchesRequiredDisclosure(string $content): bool
    {
        /** @var array<string> $keywords */
        $keywords = $this->ruleConfig['keywords'] ?? [];
        /** @var string $disclosureText */
        $disclosureText = $this->ruleConfig['disclosure_text'] ?? '';

        $lowerContent = mb_strtolower($content);
        $hasKeyword = false;

        foreach ($keywords as $keyword) {
            if (str_contains($lowerContent, mb_strtolower($keyword))) {
                $hasKeyword = true;

                break;
            }
        }

        if (! $hasKeyword) {
            return false;
        }

        return ! str_contains($lowerContent, mb_strtolower($disclosureText));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function validateConfig(SafetyRuleType $type, array $config): void
    {
        match ($type) {
            SafetyRuleType::BlockedWord => self::validateBlockedWordConfig($config),
            SafetyRuleType::RequiredDisclosure => self::validateRequiredDisclosureConfig($config),
            SafetyRuleType::CustomCheck => null,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function validateBlockedWordConfig(array $config): void
    {
        if (! isset($config['words']) || ! is_array($config['words']) || $config['words'] === []) {
            throw new InvalidSafetyRuleConfigException('blocked_word rule requires a non-empty "words" array.');
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function validateRequiredDisclosureConfig(array $config): void
    {
        if (! isset($config['keywords']) || ! is_array($config['keywords']) || $config['keywords'] === []) {
            throw new InvalidSafetyRuleConfigException('required_disclosure rule requires a non-empty "keywords" array.');
        }

        if (! isset($config['disclosure_text']) || ! is_string($config['disclosure_text']) || $config['disclosure_text'] === '') {
            throw new InvalidSafetyRuleConfigException('required_disclosure rule requires a non-empty "disclosure_text" string.');
        }
    }
}
