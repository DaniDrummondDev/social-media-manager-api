<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum AdStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::PendingReview => 'Em Revisao',
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
            self::Completed => 'Concluido',
            self::Rejected => 'Rejeitado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::PendingReview, self::Cancelled], true),
            self::PendingReview => in_array($target, [self::Active, self::Rejected], true),
            self::Active => in_array($target, [self::Paused, self::Completed, self::Cancelled], true),
            self::Paused => in_array($target, [self::Active, self::Completed, self::Cancelled], true),
            self::Completed, self::Rejected, self::Cancelled => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled], true);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::PendingReview, self::Active, self::Paused], true);
    }
}
