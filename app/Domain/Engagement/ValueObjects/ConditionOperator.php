<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum ConditionOperator: string
{
    case Contains = 'contains';
    case Equals = 'equals';
    case In = 'in';
    case NotContains = 'not_contains';
}
