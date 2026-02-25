<?php

declare(strict_types=1);

use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Billing\ValueObjects\UsageResourceType;

it('creates from array with correct properties', function () {
    $limits = PlanLimits::fromArray([
        'members' => 10,
        'social_accounts' => 15,
        'publications_month' => 500,
        'ai_generations_month' => 200,
        'storage_gb' => 50,
        'active_campaigns' => 20,
        'automations' => 10,
        'webhooks' => 5,
        'crm_connections' => 3,
        'reports_month' => 30,
        'analytics_retention_days' => 365,
    ]);

    expect($limits->members)->toBe(10)
        ->and($limits->socialAccounts)->toBe(15)
        ->and($limits->publicationsMonth)->toBe(500)
        ->and($limits->aiGenerationsMonth)->toBe(200)
        ->and($limits->storageGb)->toBe(50)
        ->and($limits->activeCampaigns)->toBe(20)
        ->and($limits->automations)->toBe(10)
        ->and($limits->webhooks)->toBe(5)
        ->and($limits->crmConnections)->toBe(3)
        ->and($limits->reportsMonth)->toBe(30)
        ->and($limits->analyticsRetentionDays)->toBe(365);
});

it('returns correct limit for each UsageResourceType', function (UsageResourceType $resource, int $expected) {
    $limits = PlanLimits::fromArray([
        'members' => 10,
        'social_accounts' => 15,
        'publications_month' => 500,
        'ai_generations_month' => 200,
        'storage_gb' => 5,
        'active_campaigns' => 20,
        'automations' => 10,
        'webhooks' => 5,
        'reports_month' => 30,
    ]);

    expect($limits->getLimit($resource))->toBe($expected);
})->with([
    'publications' => [UsageResourceType::Publications, 500],
    'ai generations' => [UsageResourceType::AiGenerations, 200],
    'storage bytes' => [UsageResourceType::StorageBytes, 5_368_709_120],
    'members' => [UsageResourceType::Members, 10],
    'social accounts' => [UsageResourceType::SocialAccounts, 15],
    'campaigns' => [UsageResourceType::Campaigns, 20],
    'automations' => [UsageResourceType::Automations, 10],
    'webhooks' => [UsageResourceType::Webhooks, 5],
    'reports' => [UsageResourceType::Reports, 30],
]);

it('returns -1 bytes for unlimited storage', function () {
    $limits = PlanLimits::fromArray([
        'storage_gb' => -1,
    ]);

    expect($limits->getLimit(UsageResourceType::StorageBytes))->toBe(-1);
});

it('isUnlimited returns true for -1', function () {
    $limits = PlanLimits::fromArray([
        'publications_month' => -1,
    ]);

    expect($limits->isUnlimited(UsageResourceType::Publications))->toBeTrue();
});

it('isUnlimited returns false for positive value', function () {
    $limits = PlanLimits::fromArray([
        'publications_month' => 500,
    ]);

    expect($limits->isUnlimited(UsageResourceType::Publications))->toBeFalse();
});

it('converts storage GB to bytes correctly', function () {
    $limits = PlanLimits::fromArray([
        'storage_gb' => 5,
    ]);

    expect($limits->getLimit(UsageResourceType::StorageBytes))->toBe(5 * 1_073_741_824);
});

it('returns correct array from toArray', function () {
    $data = [
        'members' => 10,
        'social_accounts' => 15,
        'publications_month' => 500,
        'ai_generations_month' => 200,
        'storage_gb' => 50,
        'active_campaigns' => 20,
        'automations' => 10,
        'webhooks' => 5,
        'crm_connections' => 3,
        'reports_month' => 30,
        'analytics_retention_days' => 365,
    ];

    $limits = PlanLimits::fromArray($data);

    expect($limits->toArray())->toBe($data);
});
