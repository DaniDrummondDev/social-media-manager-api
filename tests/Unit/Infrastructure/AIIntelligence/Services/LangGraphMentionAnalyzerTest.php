<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\MentionAnalysisResult;
use App\Infrastructure\AIIntelligence\Services\LangGraphMentionAnalyzer;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;

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

it('throws exception on circuit open', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new AiAgentsCircuitOpenException('social_listening'));

    $this->analyzer->analyzeMention(
        organizationId: 'org-123',
        mention: ['text' => 'Great product!', 'author' => 'user1'],
        brandContext: ['name' => 'TestBrand'],
    );
})->throws(AiAgentsCircuitOpenException::class);

it('handles timeout exception correctly', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException('social_listening', 'job-mention-timeout'));

    $this->analyzer->analyzeMention(
        organizationId: 'org-timeout-mention',
        mention: ['text' => 'Timeout mention', 'author' => 'user_timeout'],
        brandContext: ['name' => 'TimeoutBrand'],
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException::class);

it('handles request exception correctly', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsRequestException('social_listening', 'Network error'));

    $this->analyzer->analyzeMention(
        organizationId: 'org-error-mention',
        mention: ['text' => 'Error mention', 'author' => 'user_error'],
        brandContext: ['name' => 'ErrorBrand'],
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsRequestException::class);

it('passes language parameter correctly', function () {
    $this->client->shouldReceive('dispatch')
        ->with('social_listening', Mockery::on(function ($payload) {
            return $payload['language'] === 'en-US';
        }))
        ->once()
        ->andReturn([
            'result' => ['sentiment' => 'neutral', 'intent' => 'inquiry'],
            'metadata' => ['total_tokens' => 250, 'total_cost' => 0.025, 'duration_ms' => 3000],
        ]);

    $result = $this->analyzer->analyzeMention(
        organizationId: 'org-english',
        mention: ['text' => 'Great service!', 'author' => 'user_en'],
        brandContext: ['name' => 'EnglishBrand'],
        language: 'en-US',
    );

    expect($result)->toBeInstanceOf(MentionAnalysisResult::class)
        ->and($result->output['sentiment'])->toBe('neutral');
});

it('defaults language to pt-BR when not provided', function () {
    $this->client->shouldReceive('dispatch')
        ->with('social_listening', Mockery::on(function ($payload) {
            return $payload['language'] === 'pt-BR';
        }))
        ->once()
        ->andReturn([
            'result' => ['sentiment' => 'positive', 'intent' => 'feedback'],
            'metadata' => ['total_tokens' => 350, 'total_cost' => 0.035, 'duration_ms' => 3500],
        ]);

    $result = $this->analyzer->analyzeMention(
        organizationId: 'org-default-lang',
        mention: ['text' => 'Produto excelente!', 'author' => 'user_br'],
        brandContext: ['name' => 'BrazilianBrand'],
    );

    expect($result)->toBeInstanceOf(MentionAnalysisResult::class)
        ->and($result->output['sentiment'])->toBe('positive');
});
