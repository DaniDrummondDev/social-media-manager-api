<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Contracts;

interface AdPlatformInterface
{
    /**
     * Initiate OAuth connection flow with the ad platform.
     *
     * @return array{auth_url: string, state: string}
     */
    public function connect(string $redirectUri, string $state): array;

    /**
     * Exchange authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string, account_id: string, account_name: string, scopes: array<string>}
     */
    public function handleCallback(string $authorizationCode, string $redirectUri): array;

    /**
     * Create a campaign on the ad platform.
     *
     * @param  array<string, mixed>  $params  Provider-specific parameters
     * @return array{campaign_id: string}
     */
    public function createCampaign(
        string $accessToken,
        string $accountId,
        string $name,
        string $objective,
        array $params = [],
    ): array;

    /**
     * Create an ad set (ad group) within a campaign with targeting.
     *
     * @param  array<string, mixed>  $targeting  Normalized targeting specification
     * @param  array<string, mixed>  $params  Provider-specific parameters
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
    ): array;

    /**
     * Create an ad creative that promotes an existing post.
     *
     * @return array{ad_id: string}
     */
    public function createAd(
        string $accessToken,
        string $accountId,
        string $adSetId,
        string $externalPostId,
        string $name,
    ): array;

    /**
     * Get current status of an ad from the platform.
     *
     * @return array{status: string, review_feedback: ?string, effective_status: string}
     */
    public function getAdStatus(string $accessToken, string $accountId, string $adId): array;

    /**
     * Fetch performance metrics for an ad.
     *
     * @return array{impressions: int, reach: int, clicks: int, spend_cents: int, conversions: int}
     */
    public function getMetrics(
        string $accessToken,
        string $accountId,
        string $adId,
        string $dateFrom,
        string $dateTo,
    ): array;

    /**
     * Search interests available for targeting on this platform.
     *
     * @return array<array{id: string, name: string, audience_size: ?int}>
     */
    public function searchInterests(string $accessToken, string $query, int $limit = 25): array;

    /**
     * Delete/stop an ad on the platform.
     */
    public function deleteAd(string $accessToken, string $accountId, string $adId): void;
}
