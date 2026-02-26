<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->user = $this->createUserInDb();
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Create a listening query that reports can reference
    $this->queryId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_queries')->insert([
        'id' => $this->queryId,
        'organization_id' => $this->orgId,
        'name' => 'Report Test Query',
        'type' => 'keyword',
        'value' => 'report keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('generates a report with 201', function () {
    $response = $this->postJson('/api/v1/listening/reports', [
        'query_ids' => [$this->queryId],
        'period_from' => '2026-01-01',
        'period_to' => '2026-01-31',
    ], $this->headers);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'organization_id',
                    'query_ids',
                    'period_from',
                    'period_to',
                    'total_mentions',
                    'sentiment_breakdown',
                    'top_authors',
                    'top_keywords',
                    'platform_breakdown',
                    'status',
                    'file_path',
                    'generated_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data.type'))->toBe('listening_report');
    expect($response->json('data.attributes.query_ids'))->toBe([$this->queryId]);
    expect($response->json('data.attributes.status'))->toBe('pending');
});

it('lists reports with 200', function () {
    $now = now()->toDateTimeString();

    DB::table('listening_reports')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'query_ids' => json_encode([$this->queryId]),
        'period_from' => '2026-01-01',
        'period_to' => '2026-01-31',
        'total_mentions' => 150,
        'sentiment_breakdown' => json_encode(['positive' => 80, 'neutral' => 50, 'negative' => 20]),
        'top_authors' => json_encode([]),
        'top_keywords' => json_encode([]),
        'platform_breakdown' => json_encode([]),
        'status' => 'completed',
        'file_path' => null,
        'generated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('listening_reports')->insert([
        'id' => Str::uuid()->toString(),
        'organization_id' => $this->orgId,
        'query_ids' => json_encode([$this->queryId]),
        'period_from' => '2026-02-01',
        'period_to' => '2026-02-28',
        'total_mentions' => 0,
        'sentiment_breakdown' => json_encode(['positive' => 0, 'neutral' => 0, 'negative' => 0]),
        'top_authors' => json_encode([]),
        'top_keywords' => json_encode([]),
        'platform_breakdown' => json_encode([]),
        'status' => 'pending',
        'file_path' => null,
        'generated_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson('/api/v1/listening/reports', $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'organization_id',
                        'query_ids',
                        'period_from',
                        'period_to',
                        'status',
                    ],
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBeGreaterThanOrEqual(2);
});

it('shows a report with 200', function () {
    $reportId = Str::uuid()->toString();
    $now = now()->toDateTimeString();

    DB::table('listening_reports')->insert([
        'id' => $reportId,
        'organization_id' => $this->orgId,
        'query_ids' => json_encode([$this->queryId]),
        'period_from' => '2026-01-01',
        'period_to' => '2026-01-31',
        'total_mentions' => 200,
        'sentiment_breakdown' => json_encode(['positive' => 100, 'neutral' => 70, 'negative' => 30]),
        'top_authors' => json_encode([['username' => 'topuser', 'count' => 10]]),
        'top_keywords' => json_encode([['keyword' => 'brand', 'count' => 50]]),
        'platform_breakdown' => json_encode([['platform' => 'instagram', 'count' => 200, 'percentage' => 100.0]]),
        'status' => 'completed',
        'file_path' => '/reports/report-123.pdf',
        'generated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $response = $this->getJson("/api/v1/listening/reports/{$reportId}", $this->headers);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'organization_id',
                    'query_ids',
                    'period_from',
                    'period_to',
                    'total_mentions',
                    'sentiment_breakdown',
                    'top_authors',
                    'top_keywords',
                    'platform_breakdown',
                    'status',
                    'file_path',
                    'generated_at',
                    'created_at',
                ],
            ],
        ]);

    expect($response->json('data.id'))->toBe($reportId);
    expect($response->json('data.type'))->toBe('listening_report');
    expect($response->json('data.attributes.total_mentions'))->toBe(200);
    expect($response->json('data.attributes.status'))->toBe('completed');
});

it('returns 401 without auth', function () {
    $response = $this->getJson('/api/v1/listening/reports');

    $response->assertStatus(401);
});

it('isolates by organization — cannot see other org reports', function () {
    $now = now()->toDateTimeString();

    $ownReportId = Str::uuid()->toString();

    DB::table('listening_reports')->insert([
        'id' => $ownReportId,
        'organization_id' => $this->orgId,
        'query_ids' => json_encode([$this->queryId]),
        'period_from' => '2026-01-01',
        'period_to' => '2026-01-31',
        'total_mentions' => 50,
        'sentiment_breakdown' => json_encode(['positive' => 30, 'neutral' => 15, 'negative' => 5]),
        'top_authors' => json_encode([]),
        'top_keywords' => json_encode([]),
        'platform_breakdown' => json_encode([]),
        'status' => 'completed',
        'file_path' => null,
        'generated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Create another org with a different user
    $otherUser = $this->createUserInDb();
    $otherOrgId = $this->createOrgWithOwner($otherUser['id'])['org']['id'];

    $otherQueryId = Str::uuid()->toString();

    DB::table('listening_queries')->insert([
        'id' => $otherQueryId,
        'organization_id' => $otherOrgId,
        'name' => 'Other Query',
        'type' => 'keyword',
        'value' => 'other keyword',
        'platforms' => json_encode(['instagram']),
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $otherReportId = Str::uuid()->toString();

    DB::table('listening_reports')->insert([
        'id' => $otherReportId,
        'organization_id' => $otherOrgId,
        'query_ids' => json_encode([$otherQueryId]),
        'period_from' => '2026-01-01',
        'period_to' => '2026-01-31',
        'total_mentions' => 75,
        'sentiment_breakdown' => json_encode(['positive' => 40, 'neutral' => 25, 'negative' => 10]),
        'top_authors' => json_encode([]),
        'top_keywords' => json_encode([]),
        'platform_breakdown' => json_encode([]),
        'status' => 'completed',
        'file_path' => null,
        'generated_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // List should only return own org reports
    $listResponse = $this->getJson('/api/v1/listening/reports', $this->headers);

    $listResponse->assertStatus(200);

    $ids = array_column($listResponse->json('data'), 'id');
    expect($ids)->toContain($ownReportId);
    expect($ids)->not->toContain($otherReportId);

    // Show other org's report should fail
    $showResponse = $this->getJson("/api/v1/listening/reports/{$otherReportId}", $this->headers);

    $showResponse->assertStatus(422);
});
