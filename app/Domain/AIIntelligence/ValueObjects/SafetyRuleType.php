<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum SafetyRuleType: string
{
    case BlockedWord = 'blocked_word';
    case RequiredDisclosure = 'required_disclosure';
    case CustomCheck = 'custom_check';
}
