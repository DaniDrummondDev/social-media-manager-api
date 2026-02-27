<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\CrmConversionAttribution;
use App\Domain\AIIntelligence\Events\CrmConversionAttributed;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Shared\ValueObjects\Uuid;

function createAttribution(array $overrides = []): CrmConversionAttribution
{
    return CrmConversionAttribution::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        crmConnectionId: $overrides['crmConnectionId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        crmEntityType: $overrides['crmEntityType'] ?? 'deal',
        crmEntityId: $overrides['crmEntityId'] ?? 'deal-ext-123',
        attributionType: $overrides['attributionType'] ?? AttributionType::DealClosed,
        attributionValue: array_key_exists('attributionValue', $overrides) ? $overrides['attributionValue'] : 5000.00,
        currency: array_key_exists('currency', $overrides) ? $overrides['currency'] : 'BRL',
        crmStage: array_key_exists('crmStage', $overrides) ? $overrides['crmStage'] : 'closed_won',
        interactionData: $overrides['interactionData'] ?? ['source' => 'instagram'],
        userId: $overrides['userId'] ?? (string) Uuid::generate(),
    );
}

it('creates with CrmConversionAttributed event', function () {
    $orgId = Uuid::generate();
    $contentId = Uuid::generate();

    $attribution = createAttribution([
        'organizationId' => $orgId,
        'contentId' => $contentId,
        'crmEntityType' => 'deal',
        'attributionType' => AttributionType::DealClosed,
        'attributionValue' => 10000.00,
    ]);

    expect($attribution->organizationId)->toEqual($orgId)
        ->and($attribution->contentId)->toEqual($contentId)
        ->and($attribution->crmEntityType)->toBe('deal')
        ->and($attribution->attributionType)->toBe(AttributionType::DealClosed)
        ->and($attribution->attributionValue)->toBe(10000.00)
        ->and($attribution->currency)->toBe('BRL')
        ->and($attribution->crmStage)->toBe('closed_won')
        ->and($attribution->domainEvents)->toHaveCount(1)
        ->and($attribution->domainEvents[0])->toBeInstanceOf(CrmConversionAttributed::class)
        ->and($attribution->domainEvents[0]->crmEntityType)->toBe('deal')
        ->and($attribution->domainEvents[0]->attributionType)->toBe('deal_closed')
        ->and($attribution->domainEvents[0]->attributionValue)->toBe(10000.00);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $attribution = CrmConversionAttribution::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        crmConnectionId: Uuid::generate(),
        contentId: Uuid::generate(),
        crmEntityType: 'contact',
        crmEntityId: 'contact-ext-456',
        attributionType: AttributionType::LeadCapture,
        attributionValue: null,
        currency: null,
        crmStage: null,
        interactionData: [],
        attributedAt: $now,
        createdAt: $now,
    );

    expect($attribution->id)->toEqual($id)
        ->and($attribution->crmEntityType)->toBe('contact')
        ->and($attribution->attributionType)->toBe(AttributionType::LeadCapture)
        ->and($attribution->domainEvents)->toBeEmpty();
});

it('hasMonetaryValue returns true for deal_closed with value', function () {
    $attribution = createAttribution([
        'attributionType' => AttributionType::DealClosed,
        'attributionValue' => 5000.00,
    ]);

    expect($attribution->hasMonetaryValue())->toBeTrue();
});

it('hasMonetaryValue returns false for lead_capture', function () {
    $attribution = createAttribution([
        'attributionType' => AttributionType::LeadCapture,
        'attributionValue' => null,
    ]);

    expect($attribution->hasMonetaryValue())->toBeFalse();
});

it('hasMonetaryValue returns false for deal_closed with zero value', function () {
    $attribution = createAttribution([
        'attributionType' => AttributionType::DealClosed,
        'attributionValue' => 0.0,
    ]);

    expect($attribution->hasMonetaryValue())->toBeFalse();
});

it('hasMonetaryValue returns false for deal_closed with null value', function () {
    $attribution = createAttribution([
        'attributionType' => AttributionType::DealClosed,
        'attributionValue' => null,
    ]);

    expect($attribution->hasMonetaryValue())->toBeFalse();
});
