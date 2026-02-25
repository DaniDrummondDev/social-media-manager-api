<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

enum CancelFeedback: string
{
    case TooExpensive = 'too_expensive';
    case MissingFeatures = 'missing_features';
    case SwitchedService = 'switched_service';
    case TooComplex = 'too_complex';
    case Other = 'other';
}
