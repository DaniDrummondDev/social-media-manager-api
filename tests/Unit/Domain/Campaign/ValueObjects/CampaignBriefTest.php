<?php

declare(strict_types=1);

use App\Domain\Campaign\ValueObjects\CampaignBrief;

it('isEmpty returns true when all fields are null', function () {
    $brief = new CampaignBrief(text: null, targetAudience: null, restrictions: null, cta: null);
    expect($brief->isEmpty())->toBeTrue();
});

it('isEmpty returns false when at least one field is set', function () {
    $brief = new CampaignBrief(text: 'Campaign about Black Friday', targetAudience: null, restrictions: null, cta: null);
    expect($brief->isEmpty())->toBeFalse();
});

it('toPromptContext includes all non-null fields', function () {
    $brief = new CampaignBrief(
        text: 'Black Friday campaign for fashion store',
        targetAudience: 'Young adults 18-30',
        restrictions: 'No aggressive language',
        cta: 'Shop now with 50% off',
    );
    $context = $brief->toPromptContext();
    expect($context)
        ->toContain('Objective: Black Friday campaign for fashion store')
        ->toContain('Target Audience: Young adults 18-30')
        ->toContain('Restrictions: No aggressive language')
        ->toContain('Desired CTA: Shop now with 50% off')
        ->toContain('[CAMPAIGN BRIEF]');
});

it('toPromptContext omits null fields', function () {
    $brief = new CampaignBrief(text: 'Simple campaign brief', targetAudience: null, restrictions: null, cta: null);
    $context = $brief->toPromptContext();
    expect($context)
        ->toContain('Objective: Simple campaign brief')
        ->not->toContain('Target Audience')
        ->not->toContain('Restrictions')
        ->not->toContain('Desired CTA');
});

it('mergeWith returns self when override is null', function () {
    $brief = new CampaignBrief(text: 'Original', targetAudience: 'Teens', restrictions: null, cta: null);
    $merged = $brief->mergeWith(null);
    expect($merged)->toBe($brief);
});

it('mergeWith preserves existing fields when override fields are null', function () {
    $existing = new CampaignBrief(text: 'Original text', targetAudience: 'Teens', restrictions: 'No violence', cta: 'Buy now');
    $override = new CampaignBrief(text: null, targetAudience: null, restrictions: null, cta: 'Updated CTA');
    $merged = $existing->mergeWith($override);
    expect($merged->text)->toBe('Original text')
        ->and($merged->targetAudience)->toBe('Teens')
        ->and($merged->restrictions)->toBe('No violence')
        ->and($merged->cta)->toBe('Updated CTA');
});

it('mergeWith overrides all fields when all provided', function () {
    $existing = new CampaignBrief(text: 'Old', targetAudience: 'Old audience', restrictions: 'Old restrictions', cta: 'Old CTA');
    $override = new CampaignBrief(text: 'New', targetAudience: 'New audience', restrictions: 'New restrictions', cta: 'New CTA');
    $merged = $existing->mergeWith($override);
    expect($merged->text)->toBe('New')
        ->and($merged->targetAudience)->toBe('New audience')
        ->and($merged->restrictions)->toBe('New restrictions')
        ->and($merged->cta)->toBe('New CTA');
});
