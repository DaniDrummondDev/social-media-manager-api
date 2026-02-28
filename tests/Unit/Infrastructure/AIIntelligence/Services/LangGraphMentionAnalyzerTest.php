<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\MentionAnalysisResult;
use App\Infrastructure\AIIntelligence\Services\LangGraphMentionAnalyzer;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

beforeEach(function () {
    $this->client = Mockery::mock(LangGraphClientInterface::class);
    $this->analyzer = new LangGraphMentionAnalyzer($this->client);
});

it('analyzes mention via LangGraph and returns result', function () {
    $mention = [
        'id' => 'mention-abc',
        'text' => 'Great product @brand! Highly recommend.',
        'platform' => 'instagram',
        'author' => 'user_123',
        'created_at' => '2026-02-20T14:30:00Z',
    ];

    $brandContext = [
        'brand_name' => 'TechBrand',
        'industry' => 'technology',
        'competitors' => ['CompetitorA', 'CompetitorB'],
    ];

    $this->client->shouldReceive('dispatch')
        ->with('social_listening', [
            'organization_id' => 'org-mention-1',
            'mention' => $mention,
            'brand_context' => $brandContext,
            'language' => 'pt-BR',
        ])
        ->once()
        ->andReturn([
            'result' => [
                'sentiment' => 'positive',
                'intent' => 'recommendation',
                'urgency' => 'low',
                'suggested_response' => 'Obrigado pelo feedback positivo!',
                'topics' => ['product_quality', 'recommendation'],
            ],
            'metadata' => ['total_tokens' => 400, 'total_cost' => 0.04, 'duration_ms' => 4500],
        ]);

    $result = $this->analyzer->analyzeMention(
        organizationId: 'org-mention-1',
        mention: $mention,
        brandContext: $brandContext,
        language: 'pt-BR',
    );

    expect($result)
        ->toBeInstanceOf(MentionAnalysisResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('sentiment')
        ->and($result->output['sentiment'])->toBe('positive')
        ->and($result->output['intent'])->toBe('recommendation')
        ->and($result->tokensInput)->toBe(400)
        ->and($result->costEstimate)->toBe(0.04)
        ->and($result->durationMs)->toBe(4500);
});
