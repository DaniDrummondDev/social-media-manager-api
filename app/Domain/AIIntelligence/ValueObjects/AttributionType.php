<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum AttributionType: string
{
    case DirectEngagement = 'direct_engagement';
    case LeadCapture = 'lead_capture';
    case DealClosed = 'deal_closed';

    public function hasMonetaryValue(): bool
    {
        return match ($this) {
            self::DealClosed => true,
            self::DirectEngagement, self::LeadCapture => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DirectEngagement => 'Engajamento Direto',
            self::LeadCapture => 'Captura de Lead',
            self::DealClosed => 'Deal Fechado',
        };
    }
}
