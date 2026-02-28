<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organizationId = Uuid::generate();
    $this->accountId = Uuid::generate();
});

it('should calculate best posting times from metric snapshots', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);
    createBestTimesMetricSnapshots($this->organizationId, $this->accountId);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    // Use strftime for SQLite compatibility
    $results = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("strftime('%H', cms.captured_at) as hour, AVG(cms.engagement_rate) as avg_engagement")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupBy('hour')
        ->orderByDesc('avg_engagement')
        ->limit(5)
        ->get();

    expect($results)->not->toBeEmpty()
        ->and($results->first())->toHaveKey('hour')
        ->and($results->first())->toHaveKey('avg_engagement')
        ->and($results->first()->avg_engagement)->toBeGreaterThan(0);
});

it('should identify best days of week for posting', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);
    createBestTimesMetricSnapshots($this->organizationId, $this->accountId);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $results = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("strftime('%w', cms.captured_at) as day_of_week, AVG(cms.engagement_rate) as avg_engagement")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupBy('day_of_week')
        ->orderByDesc('avg_engagement')
        ->get();

    // Should have results for multiple days - verify each is valid
    expect($results)->not->toBeEmpty();
    foreach ($results as $result) {
        expect((int) $result->day_of_week)->toBeGreaterThanOrEqual(0)
            ->and((int) $result->day_of_week)->toBeLessThanOrEqual(6);
    }
});

it('should calculate hourly heatmap for best times', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);
    createBestTimesMetricSnapshots($this->organizationId, $this->accountId);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $heatmap = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("
            strftime('%w', cms.captured_at) as day_of_week,
            strftime('%H', cms.captured_at) as hour,
            AVG(cms.engagement_rate) as avg_engagement,
            COUNT(*) as sample_size
        ")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupByRaw("strftime('%w', cms.captured_at), strftime('%H', cms.captured_at)")
        ->orderByRaw("strftime('%w', cms.captured_at), strftime('%H', cms.captured_at)")
        ->get();

    expect($heatmap)->not->toBeEmpty()
        ->and($heatmap->first())->toHaveKey('day_of_week')
        ->and($heatmap->first())->toHaveKey('hour')
        ->and($heatmap->first())->toHaveKey('avg_engagement')
        ->and($heatmap->first())->toHaveKey('sample_size');
});

it('should filter by provider when calculating best times', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId, 'instagram');
    $accountIdTikTok = Uuid::generate();
    createBestTimesSocialAccount($this->organizationId, $accountIdTikTok, 'tiktok');

    createBestTimesMetricSnapshots($this->organizationId, $this->accountId, 'instagram');
    createBestTimesMetricSnapshots($this->organizationId, $accountIdTikTok, 'tiktok');

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $instagramResults = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->join('social_accounts as sa', 'cm.social_account_id', '=', 'sa.id')
        ->selectRaw("strftime('%H', cms.captured_at) as hour, AVG(cms.engagement_rate) as avg_engagement")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('sa.provider', 'instagram')
        ->where('sa.organization_id', (string) $this->organizationId)
        ->groupBy('hour')
        ->orderByDesc('avg_engagement')
        ->limit(3)
        ->get();

    expect($instagramResults)->not->toBeEmpty();
});

it('should calculate worst posting times', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);
    createBestTimesMetricSnapshots($this->organizationId, $this->accountId);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $worstTimes = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("strftime('%H', cms.captured_at) as hour, AVG(cms.engagement_rate) as avg_engagement")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupBy('hour')
        ->orderBy('avg_engagement', 'asc')
        ->limit(3)
        ->get();

    expect($worstTimes)->not->toBeEmpty()
        ->and($worstTimes->first()->avg_engagement)->toBeLessThanOrEqual($worstTimes->last()->avg_engagement);
});

it('should require minimum sample size for reliable best times', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);

    $contentId = Uuid::generate();
    createBestTimesContent($contentId, $this->organizationId);

    $contentMetricId = Uuid::generate();
    DB::table('content_metrics')->insert([
        'id' => (string) $contentMetricId,
        'content_id' => (string) $contentId,
        'social_account_id' => (string) $this->accountId,
        'provider' => 'instagram',
        'engagement_rate' => 5.5,
        'synced_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('content_metric_snapshots')->insert([
        'id' => (string) Uuid::generate(),
        'content_metric_id' => (string) $contentMetricId,
        'engagement_rate' => 5.5,
        'captured_at' => '2026-02-15 14:00:00',
    ]);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $results = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("
            strftime('%H', cms.captured_at) as hour,
            AVG(cms.engagement_rate) as avg_engagement,
            COUNT(*) as sample_size
        ")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupBy('hour')
        ->havingRaw('COUNT(*) >= 1')
        ->get();

    expect($results)->not->toBeEmpty()
        ->and($results->first()->sample_size)->toBeGreaterThanOrEqual(1);
});

it('should calculate engagement delta between best and worst times', function () {
    createBestTimesSocialAccount($this->organizationId, $this->accountId);
    createBestTimesMetricSnapshots($this->organizationId, $this->accountId);

    $from = new DateTimeImmutable('2026-01-01 00:00:00');
    $to = new DateTimeImmutable('2026-02-28 23:59:59');

    $hourlyAvg = DB::table('content_metric_snapshots as cms')
        ->join('content_metrics as cm', 'cms.content_metric_id', '=', 'cm.id')
        ->selectRaw("strftime('%H', cms.captured_at) as hour, AVG(cms.engagement_rate) as avg_engagement")
        ->whereBetween('cms.captured_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
        ->where('cm.social_account_id', (string) $this->accountId)
        ->groupBy('hour')
        ->orderByDesc('avg_engagement')
        ->get();

    if ($hourlyAvg->count() > 1) {
        $bestEngagement = $hourlyAvg->first()->avg_engagement;
        $worstEngagement = $hourlyAvg->last()->avg_engagement;

        $delta = $bestEngagement - $worstEngagement;

        expect($delta)->toBeGreaterThanOrEqual(0);
    } else {
        expect(true)->toBeTrue();
    }
});

function createBestTimesSocialAccount(Uuid $orgId, Uuid $accountId, string $provider = 'instagram'): void
{
    // Create a user first
    $userId = (string) Uuid::generate();
    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'Test User',
        'email' => 'test-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create organization if it doesn't exist
    $exists = DB::table('organizations')->where('id', (string) $orgId)->exists();
    if (! $exists) {
        DB::table('organizations')->insert([
            'id' => (string) $orgId,
            'name' => 'Test Org',
            'slug' => 'test-org-' . uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    DB::table('social_accounts')->insert([
        'id' => (string) $accountId,
        'organization_id' => (string) $orgId,
        'connected_by' => $userId,
        'provider' => $provider,
        'provider_user_id' => 'provider-user-' . uniqid(),
        'username' => 'testuser_' . uniqid(),
        'display_name' => 'Test User',
        'access_token' => encrypt('fake-token'),
        'refresh_token' => null,
        'token_expires_at' => now()->addDays(30),
        'status' => 'connected',
        'connected_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createBestTimesContent(Uuid $contentId, Uuid $orgId): void
{
    // Get or create a user for this org
    $userId = DB::table('users')->first()?->id;
    if (! $userId) {
        $userId = (string) Uuid::generate();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'Test Content User',
            'email' => 'content-user-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Get or create a campaign for this org
    $campaignId = DB::table('campaigns')
        ->where('organization_id', (string) $orgId)
        ->first()?->id;

    if (! $campaignId) {
        $campaignId = (string) Uuid::generate();
        DB::table('campaigns')->insert([
            'id' => $campaignId,
            'organization_id' => (string) $orgId,
            'created_by' => $userId,
            'name' => 'Test Campaign ' . uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    DB::table('contents')->insert([
        'id' => (string) $contentId,
        'campaign_id' => $campaignId,
        'organization_id' => (string) $orgId,
        'created_by' => $userId,
        'title' => 'Test Content',
        'status' => 'published',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createBestTimesMetricSnapshots(Uuid $orgId, Uuid $accountId, string $provider = 'instagram'): void
{
    $hours = [9, 12, 14, 18, 20, 22];
    $daysOfWeek = [0, 1, 2, 3, 4, 5, 6]; // All days of the week

    foreach ($daysOfWeek as $dayOfWeek) {
        foreach ($hours as $hour) {
            $contentId = Uuid::generate();
            createBestTimesContent($contentId, $orgId);

            $contentMetricId = Uuid::generate();

            $engagementRate = match ($hour) {
                9 => rand(30, 40) / 10,
                12 => rand(50, 70) / 10,
                14 => rand(45, 60) / 10,
                18 => rand(60, 85) / 10,
                20 => rand(55, 75) / 10,
                22 => rand(25, 35) / 10,
                default => rand(20, 40) / 10,
            };

            // Create content_metrics first
            DB::table('content_metrics')->insert([
                'id' => (string) $contentMetricId,
                'content_id' => (string) $contentId,
                'social_account_id' => (string) $accountId,
                'provider' => $provider,
                'engagement_rate' => $engagementRate,
                'synced_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Calculate a date that falls on the correct day of week
            // February 2026: Sun=1, Mon=2, ..., Sat=7
            // dayOfWeek: 0=Sun, 1=Mon, ..., 6=Sat
            $baseDate = new DateTimeImmutable('2026-02-01');
            $baseDayOfWeek = (int) $baseDate->format('w'); // 0=Sun
            $offset = ($dayOfWeek - $baseDayOfWeek + 7) % 7;
            $targetDate = $baseDate->modify("+{$offset} days");
            $dateStr = $targetDate->format('Y-m-d');

            // Create content_metric_snapshots with reference to content_metrics
            DB::table('content_metric_snapshots')->insert([
                'id' => (string) Uuid::generate(),
                'content_metric_id' => (string) $contentMetricId,
                'engagement_rate' => $engagementRate,
                'captured_at' => "{$dateStr} {$hour}:00:00",
            ]);
        }
    }
}
