<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\AttributionType;

it('has three cases', function () {
    expect(AttributionType::cases())->toHaveCount(3);
});

it('has correct string values', function () {
    expect(AttributionType::DirectEngagement->value)->toBe('direct_engagement')
        ->and(AttributionType::LeadCapture->value)->toBe('lead_capture')
        ->and(AttributionType::DealClosed->value)->toBe('deal_closed');
});

it('hasMonetaryValue only for deal_closed', function () {
    expect(AttributionType::DealClosed->hasMonetaryValue())->toBeTrue()
        ->and(AttributionType::DirectEngagement->hasMonetaryValue())->toBeFalse()
        ->and(AttributionType::LeadCapture->hasMonetaryValue())->toBeFalse();
});

it('has correct labels', function () {
    expect(AttributionType::DirectEngagement->label())->toBe('Engajamento Direto')
        ->and(AttributionType::LeadCapture->label())->toBe('Captura de Lead')
        ->and(AttributionType::DealClosed->label())->toBe('Deal Fechado');
});

it('creates from string value', function () {
    expect(AttributionType::from('direct_engagement'))->toBe(AttributionType::DirectEngagement)
        ->and(AttributionType::from('lead_capture'))->toBe(AttributionType::LeadCapture)
        ->and(AttributionType::from('deal_closed'))->toBe(AttributionType::DealClosed);
});
