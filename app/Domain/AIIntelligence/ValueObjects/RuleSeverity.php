<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum RuleSeverity: string
{
    case Warning = 'warning';
    case Block = 'block';
}
