<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

final readonly class RuleCondition
{
    public function __construct(
        public string $field,
        public ConditionOperator $operator,
        public string $value,
        public bool $isCaseSensitive = false,
        public int $position = 0,
    ) {}

    public function evaluate(string $text, ?Sentiment $sentiment, ?string $authorName): bool
    {
        $target = match ($this->field) {
            'keyword' => $text,
            'sentiment' => $sentiment !== null ? $sentiment->value : '',
            'author_name' => $authorName ?? '',
            default => '',
        };

        return $this->match($target);
    }

    private function match(string $target): bool
    {
        $value = $this->value;
        $subject = $target;

        if (! $this->isCaseSensitive) {
            $value = mb_strtolower($value);
            $subject = mb_strtolower($subject);
        }

        return match ($this->operator) {
            ConditionOperator::Contains => str_contains($subject, $value),
            ConditionOperator::Equals => $subject === $value,
            ConditionOperator::In => in_array($subject, array_map('trim', explode(',', $value)), true),
            ConditionOperator::NotContains => ! str_contains($subject, $value),
        };
    }
}
