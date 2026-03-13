<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\ContentProfileResult;
use App\Infrastructure\AIIntelligence\Services\LangGraphContentProfiler;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;

beforeEach(function () {
    $this->client = Mockery::mock(LangGraphClientInterface::class);
    $this->profiler = new LangGraphContentProfiler($this->client);
});

it('analyzes profile via LangGraph and returns result', function () {
    $publishedContents = [
        ['id' => 'content-1', 'title' => 'Post about AI', 'body' => 'AI is transforming...'],
        ['id' => 'content-2', 'title' => 'Marketing tips', 'body' => 'Top 10 marketing...'],
    ];

    $metrics = [
        ['content_id' => 'content-1', 'likes' => 150, 'comments' => 30, 'shares' => 20],
        ['content_id' => 'content-2', 'likes' => 200, 'comments' => 45, 'shares' => 35],
    ];

    $currentStyleProfile = ['tone' => 'professional', 'topics' => ['ai', 'marketing']];

    $this->client->shouldReceive('dispatch')
        ->with('content_dna', [
            'organization_id' => 'org-789',
            'published_contents' => $publishedContents,
            'metrics' => $metrics,
            'current_style_profile' => $currentStyleProfile,
            'time_window' => 'last_90_days',
        ])
        ->once()
        ->andReturn([
            'result' => [
                'content_dna' => [
                    'primary_topics' => ['ai', 'marketing', 'technology'],
                    'tone_distribution' => ['professional' => 0.7, 'casual' => 0.3],
                    'engagement_patterns' => ['best_day' => 'tuesday', 'best_hour' => 10],
                ],
            ],
            'metadata' => ['total_tokens' => 800, 'total_cost' => 0.08, 'duration_ms' => 7000],
        ]);

    $result = $this->profiler->analyzeProfile(
        organizationId: 'org-789',
        publishedContents: $publishedContents,
        metrics: $metrics,
        currentStyleProfile: $currentStyleProfile,
    );

    expect($result)
        ->toBeInstanceOf(ContentProfileResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('content_dna')
        ->and($result->tokensInput)->toBe(800)
        ->and($result->costEstimate)->toBe(0.08)
        ->and($result->durationMs)->toBe(7000);
});

it('throws exception when LangGraph times out', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new AiAgentsTimeoutException('content_dna', 'job-timeout'));

    $this->profiler->analyzeProfile(
        organizationId: 'org-123',
        publishedContents: [['id' => 1, 'text' => 'test']],
        metrics: [['likes' => 100]],
    );
})->throws(AiAgentsTimeoutException::class);

it('handles circuit open exception correctly', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException('content_dna'));

    $this->profiler->analyzeProfile(
        organizationId: 'org-circuit-open',
        publishedContents: [['id' => 1, 'text' => 'test']],
        metrics: [['likes' => 50]],
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException::class);

it('handles request exception correctly', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsRequestException('content_dna', 'HTTP 503 Service Unavailable'));

    $this->profiler->analyzeProfile(
        organizationId: 'org-request-error',
        publishedContents: [['id' => 2, 'text' => 'another test']],
        metrics: [['likes' => 100]],
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsRequestException::class);

it('passes current_style_profile correctly when provided', function () {
    $styleProfile = ['tone' => 'casual', 'topics' => ['tech', 'lifestyle']];

    $this->client->shouldReceive('dispatch')
        ->with('content_dna', Mockery::on(function ($payload) use ($styleProfile) {
            return $payload['current_style_profile'] === $styleProfile;
        }))
        ->once()
        ->andReturn([
            'result' => ['content_dna' => ['updated' => true]],
            'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
        ]);

    $result = $this->profiler->analyzeProfile(
        organizationId: 'org-with-profile',
        publishedContents: [['id' => 3, 'text' => 'content']],
        metrics: [['likes' => 75]],
        currentStyleProfile: $styleProfile,
    );

    expect($result)->toBeInstanceOf(ContentProfileResult::class);
});

it('passes null current_style_profile when not provided', function () {
    $this->client->shouldReceive('dispatch')
        ->with('content_dna', Mockery::on(function ($payload) {
            return $payload['current_style_profile'] === null;
        }))
        ->once()
        ->andReturn([
            'result' => ['content_dna' => ['new_profile' => true]],
            'metadata' => ['total_tokens' => 400, 'total_cost' => 0.04, 'duration_ms' => 2500],
        ]);

    $result = $this->profiler->analyzeProfile(
        organizationId: 'org-no-profile',
        publishedContents: [['id' => 4, 'text' => 'first content']],
        metrics: [['likes' => 25]],
    );

    expect($result)->toBeInstanceOf(ContentProfileResult::class);
});

it('includes time_window in payload', function () {
    $this->client->shouldReceive('dispatch')
        ->with('content_dna', Mockery::on(function ($payload) {
            return $payload['time_window'] === 'last_30_days';
        }))
        ->once()
        ->andReturn([
            'result' => ['content_dna' => ['short_term' => true]],
            'metadata' => ['total_tokens' => 300, 'total_cost' => 0.03, 'duration_ms' => 2000],
        ]);

    $result = $this->profiler->analyzeProfile(
        organizationId: 'org-custom-window',
        publishedContents: [['id' => 5, 'text' => 'recent content']],
        metrics: [['likes' => 60]],
        timeWindow: 'last_30_days',
    );

    expect($result)->toBeInstanceOf(ContentProfileResult::class);
});
