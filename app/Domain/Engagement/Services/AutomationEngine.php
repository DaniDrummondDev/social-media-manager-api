<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Services;

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Engagement\Entities\Comment;
use Closure;

final readonly class AutomationEngine
{
    /**
     * @param  array<AutomationRule>  $rules
     * @param  array<BlacklistWord>  $blacklistWords
     * @param  Closure(string): int  $countTodayByRule
     */
    public function evaluate(
        Comment $comment,
        array $rules,
        array $blacklistWords,
        Closure $countTodayByRule,
    ): ?AutomationRule {
        if ($comment->isFromOwner) {
            return null;
        }

        if ($comment->isReplied()) {
            return null;
        }

        if ($this->isBlacklisted($comment->text, $blacklistWords)) {
            return null;
        }

        usort($rules, fn (AutomationRule $a, AutomationRule $b) => $a->priority <=> $b->priority);

        foreach ($rules as $rule) {
            if (! $rule->isActive) {
                continue;
            }

            if (! $rule->matchesFilters($comment->provider->value)) {
                continue;
            }

            if (! $rule->evaluateConditions($comment)) {
                continue;
            }

            $todayCount = $countTodayByRule((string) $rule->id);
            if ($todayCount >= $rule->dailyLimit) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    /**
     * @param  array<BlacklistWord>  $blacklistWords
     */
    private function isBlacklisted(string $text, array $blacklistWords): bool
    {
        foreach ($blacklistWords as $word) {
            if ($word->matches($text)) {
                return true;
            }
        }

        return false;
    }
}
