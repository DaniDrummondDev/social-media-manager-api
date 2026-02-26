<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

final readonly class DiffSummary
{
    /**
     * @param  array<array{field: string, before: string, after: string}>  $changes
     * @param  float  $changeRatio  Levenshtein distance / original length (0.0 = no change, 1.0 = total rewrite)
     */
    private function __construct(
        public array $changes,
        public float $changeRatio,
    ) {}

    /**
     * @param  array<array{field: string, before: string, after: string}>  $changes
     */
    public static function create(array $changes, float $changeRatio): self
    {
        if ($changeRatio < 0.0 || $changeRatio > 1.0) {
            throw new \DomainException('Change ratio must be between 0.0 and 1.0.');
        }

        return new self($changes, $changeRatio);
    }

    /**
     * @param  array{changes: array<array{field: string, before: string, after: string}>, change_ratio: float}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            changes: $data['changes'] ?? [],
            changeRatio: (float) ($data['change_ratio'] ?? 0.0),
        );
    }

    /**
     * @return array{changes: array<array{field: string, before: string, after: string}>, change_ratio: float}
     */
    public function toArray(): array
    {
        return [
            'changes' => $this->changes,
            'change_ratio' => $this->changeRatio,
        ];
    }

    public function isMinorEdit(): bool
    {
        return $this->changeRatio < 0.3;
    }

    public function isMajorRewrite(): bool
    {
        return $this->changeRatio >= 0.7;
    }

    /**
     * Compute a diff summary between original and edited output arrays.
     *
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $edited
     */
    public static function compute(array $original, array $edited): self
    {
        $changes = [];
        $totalLength = 0;
        $totalDistance = 0;

        $allKeys = array_unique(array_merge(array_keys($original), array_keys($edited)));

        foreach ($allKeys as $key) {
            $before = isset($original[$key]) ? (string) $original[$key] : '';
            $after = isset($edited[$key]) ? (string) $edited[$key] : '';

            if ($before !== $after) {
                $changes[] = ['field' => (string) $key, 'before' => $before, 'after' => $after];
            }

            $len = max(mb_strlen($before), mb_strlen($after));
            $totalLength += $len;
            $totalDistance += levenshtein(mb_substr($before, 0, 255), mb_substr($after, 0, 255));
        }

        $changeRatio = $totalLength > 0 ? min(1.0, $totalDistance / $totalLength) : 0.0;

        return new self($changes, round($changeRatio, 4));
    }
}
