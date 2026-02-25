<?php

declare(strict_types=1);

use App\Domain\Billing\ValueObjects\PlanFeatures;

it('creates from array with correct properties', function () {
    $features = PlanFeatures::fromArray([
        'ai_generation_basic' => true,
        'ai_generation_advanced' => true,
        'ai_intelligence' => false,
        'ai_learning' => false,
        'automations' => true,
        'webhooks' => true,
        'crm_native' => false,
        'export_pdf' => true,
        'export_csv' => true,
        'priority_publishing' => false,
    ]);

    expect($features->aiGenerationBasic)->toBeTrue()
        ->and($features->aiGenerationAdvanced)->toBeTrue()
        ->and($features->aiIntelligence)->toBeFalse()
        ->and($features->aiLearning)->toBeFalse()
        ->and($features->automations)->toBeTrue()
        ->and($features->webhooks)->toBeTrue()
        ->and($features->crmNative)->toBeFalse()
        ->and($features->exportPdf)->toBeTrue()
        ->and($features->exportCsv)->toBeTrue()
        ->and($features->priorityPublishing)->toBeFalse();
});

it('hasFeature returns true for enabled features', function (string $featureKey) {
    $features = PlanFeatures::fromArray([
        'ai_generation_basic' => true,
        'ai_generation_advanced' => true,
        'ai_intelligence' => true,
        'ai_learning' => true,
        'automations' => true,
        'webhooks' => true,
        'crm_native' => true,
        'export_pdf' => true,
        'export_csv' => true,
        'priority_publishing' => true,
    ]);

    expect($features->hasFeature($featureKey))->toBeTrue();
})->with([
    'ai_generation_basic',
    'ai_generation_advanced',
    'ai_intelligence',
    'ai_learning',
    'automations',
    'webhooks',
    'crm_native',
    'export_pdf',
    'export_csv',
    'priority_publishing',
]);

it('hasFeature returns false for disabled features', function (string $featureKey) {
    $features = PlanFeatures::fromArray([
        'ai_generation_basic' => false,
        'ai_generation_advanced' => false,
        'ai_intelligence' => false,
        'ai_learning' => false,
        'automations' => false,
        'webhooks' => false,
        'crm_native' => false,
        'export_pdf' => false,
        'export_csv' => false,
        'priority_publishing' => false,
    ]);

    expect($features->hasFeature($featureKey))->toBeFalse();
})->with([
    'ai_generation_basic',
    'ai_generation_advanced',
    'ai_intelligence',
    'ai_learning',
    'automations',
    'webhooks',
    'crm_native',
    'export_pdf',
    'export_csv',
    'priority_publishing',
]);

it('hasFeature returns false for unknown feature key', function () {
    $features = PlanFeatures::fromArray([]);

    expect($features->hasFeature('nonexistent_feature'))->toBeFalse();
});

it('returns correct array from toArray', function () {
    $data = [
        'ai_generation_basic' => true,
        'ai_generation_advanced' => false,
        'ai_intelligence' => true,
        'ai_learning' => false,
        'automations' => true,
        'webhooks' => false,
        'crm_native' => true,
        'export_pdf' => false,
        'export_csv' => true,
        'priority_publishing' => false,
    ];

    $features = PlanFeatures::fromArray($data);

    expect($features->toArray())->toBe($data);
});
