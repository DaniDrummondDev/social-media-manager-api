<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\ContentProfileResult;
use App\Infrastructure\AIIntelligence\Services\LangGraphContentProfiler;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

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
