<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Infrastructure\PaidAdvertising\Services\StubTikTokAdPlatform;

beforeEach(function () {
    $this->platform = new StubTikTokAdPlatform(config: []);
});

it('connect returns tiktok oauth url', function () {
    $result = $this->platform->connect('https://example.com/callback', 'test-state');

    expect($result)->toHaveKeys(['auth_url', 'state'])
        ->and($result['auth_url'])->toContain('tiktok.com')
        ->and($result['state'])->toBe('test-state');
});

it('handleCallback returns token data', function () {
    $result = $this->platform->handleCallback('auth-code', 'https://example.com/callback');

    expect($result)->toHaveKeys(['access_token', 'refresh_token', 'expires_at', 'account_id', 'account_name', 'scopes'])
        ->and($result['access_token'])->toStartWith('tiktok_stub_at_')
        ->and($result['account_id'])->toStartWith('tiktok_')
        ->and($result['account_name'])->toBe('Stub TikTok Ads Account');
});

it('createCampaign returns campaign id', function () {
    $result = $this->platform->createCampaign('token', 'tiktok_123', 'Campaign', 'REACH');

    expect($result)->toHaveKey('campaign_id')
        ->and($result['campaign_id'])->toStartWith('tiktok_camp_');
});

it('createAdSet returns adset id', function () {
    $result = $this->platform->createAdSet('token', 'tiktok_123', 'camp_1', 'AdGroup', 5000, 'daily', [], '2025-01-01', '2025-01-31');

    expect($result)->toHaveKey('adset_id')
        ->and($result['adset_id'])->toStartWith('tiktok_adgroup_');
});

it('createAd returns ad id', function () {
    $result = $this->platform->createAd('token', 'tiktok_123', 'adgroup_1', 'post_ext_1', 'My Ad');

    expect($result)->toHaveKey('ad_id')
        ->and($result['ad_id'])->toStartWith('tiktok_ad_');
});

it('getAdStatus returns valid status', function () {
    $result = $this->platform->getAdStatus('token', 'tiktok_123', 'ad_1');

    expect($result)->toHaveKeys(['status', 'effective_status'])
        ->and($result['status'])->toBe('ACTIVE');
});

it('searchInterests returns results', function () {
    $result = $this->platform->searchInterests('token', 'gaming');

    expect($result)->toBeArray()
        ->and($result)->not->toBeEmpty()
        ->and($result[0])->toHaveKeys(['id', 'name', 'audience_size']);
});

it('implements AdPlatformInterface', function () {
    expect($this->platform)->toBeInstanceOf(AdPlatformInterface::class);
});
