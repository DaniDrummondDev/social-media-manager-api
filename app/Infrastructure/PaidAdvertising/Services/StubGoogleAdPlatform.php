<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Services;

use App\Domain\PaidAdvertising\Contracts\AdPlatformInterface;
use DateTimeImmutable;

final class StubGoogleAdPlatform implements AdPlatformInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * @return array{auth_url: string, state: string}
     */
    public function connect(string $redirectUri, string $state): array
    {
        return [
            'auth_url' => "https://accounts.google.com/o/oauth2/auth?client_id=stub&redirect_uri={$redirectUri}&state={$state}&scope=https://www.googleapis.com/auth/adwords&response_type=code",
            'state' => $state,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string, scopes: array<string>}
     */
    public function handleCallback(string $authorizationCode, string $redirectUri): array
    {
        return [
            'access_token' => 'google_stub_at_'.bin2hex(random_bytes(16)),
            'refresh_token' => 'google_stub_rt_'.bin2hex(random_bytes(16)),
            'expires_at' => (new DateTimeImmutable('+60 days'))->format('c'),
            'account_id' => 'google_'.str_pad((string) random_int(100000000, 999999999), 9, '0'),
            'account_name' => 'Stub Google Ads Account',
            'scopes' => ['https://www.googleapis.com/auth/adwords'],
        ];
    }

    /**
     * @return array{campaign_id: string}
     */
    public function createCampaign(
        string $accessToken,
        string $accountId,
        string $name,
        string $objective,
        array $params = [],
    ): array {
        return [
            'campaign_id' => 'google_camp_'.bin2hex(random_bytes(8)),
        ];
    }

    /**
     * @return array{adset_id: string}
     */
    public function createAdSet(
        string $accessToken,
        string $accountId,
        string $campaignId,
        string $name,
        int $budgetCents,
        string $budgetType,
        array $targeting,
        string $startDate,
        string $endDate,
        array $params = [],
    ): array {
        return [
            'adset_id' => 'google_adgroup_'.bin2hex(random_bytes(8)),
        ];
    }

    /**
     * @return array{ad_id: string}
     */
    public function createAd(
        string $accessToken,
        string $accountId,
        string $adSetId,
        string $externalPostId,
        string $name,
    ): array {
        return [
            'ad_id' => 'google_ad_'.bin2hex(random_bytes(8)),
        ];
    }

    /**
     * @return array{status: string, review_feedback: ?string, effective_status: string}
     */
    public function getAdStatus(string $accessToken, string $accountId, string $adId): array
    {
        return [
            'status' => 'ACTIVE',
            'review_feedback' => null,
            'effective_status' => 'ACTIVE',
        ];
    }

    /**
     * @return array{impressions: int, reach: int, clicks: int, spend_cents: int, conversions: int}
     */
    public function getMetrics(
        string $accessToken,
        string $accountId,
        string $adId,
        string $dateFrom,
        string $dateTo,
    ): array {
        return [
            'impressions' => random_int(1000, 50000),
            'reach' => random_int(800, 40000),
            'clicks' => random_int(50, 2000),
            'spend_cents' => random_int(500, 10000),
            'conversions' => random_int(5, 200),
        ];
    }

    /**
     * @return array<array{id: string, name: string, audience_size: ?int}>
     */
    public function searchInterests(string $accessToken, string $query, int $limit = 25): array
    {
        return [
            ['id' => 'google_int_80', 'name' => 'Beauty & Personal Care', 'audience_size' => 400000000],
            ['id' => 'google_int_107', 'name' => 'Sports', 'audience_size' => 350000000],
            ['id' => 'google_int_71', 'name' => 'Finance', 'audience_size' => 300000000],
        ];
    }

    public function deleteAd(string $accessToken, string $accountId, string $adId): void {}
}
