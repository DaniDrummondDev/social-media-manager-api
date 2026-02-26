<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum SafetyCategory: string
{
    case LgpdCompliance = 'lgpd_compliance';
    case AdvertisingDisclosure = 'advertising_disclosure';
    case PlatformPolicy = 'platform_policy';
    case Sensitivity = 'sensitivity';
    case Profanity = 'profanity';
}
