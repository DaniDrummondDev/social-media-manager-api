<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use App\Infrastructure\PaidAdvertising\Services\StubGoogleAdPlatform;

beforeEach(function () {
    $this->platform = new StubGoogleAdPlatform(config: []);
});

it('connect returns google oauth url', function () {
    $result = $this->platform->connect('https://example.com/callback', 'test-state');

    expect($result)->toHaveKeys(['auth_url', 'state'])
        ->and($result['auth_url'])->toContain('accounts.google.com')
        ->and($result['state'])->toBe('test-state');
});

it('handleCallback returns token data with refresh token', function () {
    $result = $this->platform->handleCallback('auth-code', 'https://example.com/callback');

    expect($result)->toHaveKeys(['access_token', 'refresh_token', 'expires_at', 'account_id', 'account_name', 'scopes'])
        ->and($result['access_token'])->toStartWith('google_stub_at_')
        ->and($result['refresh_token'])->toStartWith('google_stub_rt_')
        ->and($result['account_id'])->toStartWith('google_')
        ->and($result['account_name'])->toBe('Stub Google Ads Account');
});

it('createCampaign returns campaign id', function () {
    $result = $this->platform->createCampaign('token', 'google_123', 'Campaign', 'DISPLAY_REACH');

    expect($result)->toHaveKey('campaign_id')
        ->and($result['campaign_id'])->toStartWith('google_camp_');
});

it('createAdSet returns adset id', function () {
    $result = $this->platform->createAdSet('token', 'google_123', 'camp_1', 'AdGroup', 5000, 'daily', [], '2025-01-01', '2025-01-31');

    expect($result)->toHaveKey('adset_id')
        ->and($result['adset_id'])->toStartWith('google_adgroup_');
});

it('createAd returns ad id', function () {
    $result = $this->platform->createAd('token', 'google_123', 'adgroup_1', 'post_ext_1', 'My Ad');

    expect($result)->toHaveKey('ad_id')
        ->and($result['ad_id'])->toStartWith('google_ad_');
});

it('getAdStatus returns valid status', function () {
    $result = $this->platform->getAdStatus('token', 'google_123', 'ad_1');

    expect($result)->toHaveKeys(['status', 'effective_status'])
        ->and($result['status'])->toBe('ACTIVE');
});

it('searchInterests returns results', function () {
    $result = $this->platform->searchInterests('token', 'finance');

    expect($result)->toBeArray()
        ->and($result)->not->toBeEmpty()
        ->and($result[0])->toHaveKeys(['id', 'name', 'audience_size']);
});

it('implements AdPlatformInterface', function () {
    expect($this->platform)->toBeInstanceOf(AdPlatformInterface::class);
});
