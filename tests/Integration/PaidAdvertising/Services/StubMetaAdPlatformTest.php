<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Infrastructure\PaidAdvertising\Services\StubMetaAdPlatform;

beforeEach(function () {
    $this->platform = new StubMetaAdPlatform(config: []);
});

it('connect returns facebook oauth url', function () {
    $result = $this->platform->connect('https://example.com/callback', 'test-state');

    expect($result)->toHaveKeys(['auth_url', 'state'])
        ->and($result['auth_url'])->toContain('facebook.com')
        ->and($result['state'])->toBe('test-state');
});

it('handleCallback returns token data', function () {
    $result = $this->platform->handleCallback('auth-code', 'https://example.com/callback');

    expect($result)->toHaveKeys(['access_token', 'refresh_token', 'expires_at', 'account_id', 'account_name', 'scopes'])
        ->and($result['access_token'])->toStartWith('meta_stub_at_')
        ->and($result['account_id'])->toStartWith('act_')
        ->and($result['account_name'])->toBe('Stub Meta Ads Account');
});

it('createCampaign returns campaign id', function () {
    $result = $this->platform->createCampaign('token', 'act_123', 'Campaign', 'REACH');

    expect($result)->toHaveKey('campaign_id')
        ->and($result['campaign_id'])->toStartWith('meta_camp_');
});

it('createAdSet returns adset id', function () {
    $result = $this->platform->createAdSet('token', 'act_123', 'camp_1', 'AdSet', 5000, 'daily', [], '2025-01-01', '2025-01-31');

    expect($result)->toHaveKey('adset_id')
        ->and($result['adset_id'])->toStartWith('meta_adset_');
});

it('createAd returns ad id', function () {
    $result = $this->platform->createAd('token', 'act_123', 'adset_1', 'post_ext_1', 'My Ad');

    expect($result)->toHaveKey('ad_id')
        ->and($result['ad_id'])->toStartWith('meta_ad_');
});

it('getAdStatus returns status and effective_status', function () {
    $result = $this->platform->getAdStatus('token', 'act_123', 'ad_1');

    expect($result)->toHaveKeys(['status', 'review_feedback', 'effective_status'])
        ->and($result['status'])->toBe('ACTIVE');
});

it('searchInterests returns array of interests', function () {
    $result = $this->platform->searchInterests('token', 'tech');

    expect($result)->toBeArray()
        ->and($result)->not->toBeEmpty()
        ->and($result[0])->toHaveKeys(['id', 'name', 'audience_size']);
});

it('implements AdPlatformInterface', function () {
    expect($this->platform)->toBeInstanceOf(AdPlatformInterface::class);
});
