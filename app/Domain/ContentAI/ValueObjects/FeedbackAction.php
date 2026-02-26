<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\ValueObjects;

enum FeedbackAction: string
{
    case Accepted = 'accepted';
    case Edited = 'edited';
    case Rejected = 'rejected';

    public function requiresEditedOutput(): bool
    {
        return $this === self::Edited;
    }
}
