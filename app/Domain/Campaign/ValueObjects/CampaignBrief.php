<?php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

final readonly class CampaignBrief
{
    public function __construct(
        public ?string $text,
        public ?string $targetAudience,
        public ?string $restrictions,
        public ?string $cta,
    ) {}

    public function isEmpty(): bool
    {
        return $this->text === null
            && $this->targetAudience === null
            && $this->restrictions === null
            && $this->cta === null;
    }

    public function toPromptContext(): string
    {
        $lines = ['[CAMPAIGN BRIEF]'];

        if ($this->text !== null) {
            $lines[] = "Objective: {$this->text}";
        }

        if ($this->targetAudience !== null) {
            $lines[] = "Target Audience: {$this->targetAudience}";
        }

        if ($this->restrictions !== null) {
            $lines[] = "Restrictions: {$this->restrictions}";
        }

        if ($this->cta !== null) {
            $lines[] = "Desired CTA: {$this->cta}";
        }

        $lines[] = '';
        $lines[] = 'Generate content based on this campaign brief context.';

        return implode("\n", $lines);
    }

    public function mergeWith(?self $override): self
    {
        if ($override === null) {
            return $this;
        }

        return new self(
            text: $override->text ?? $this->text,
            targetAudience: $override->targetAudience ?? $this->targetAudience,
            restrictions: $override->restrictions ?? $this->restrictions,
            cta: $override->cta ?? $this->cta,
        );
    }
}
