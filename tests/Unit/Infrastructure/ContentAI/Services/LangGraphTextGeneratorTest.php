<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Infrastructure\ContentAI\Services\LangGraphTextGenerator;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;
use App\Infrastructure\Shared\Exceptions\AiAgentsRequestException;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->client = Mockery::mock(LangGraphClientInterface::class);
    $this->fallback = Mockery::mock(TextGeneratorInterface::class);
    $this->planGate = Mockery::mock(AiAgentsPlanGate::class);

    $this->adapter = new LangGraphTextGenerator($this->client, $this->fallback, $this->planGate);
});

it('generates full content via LangGraph on happy path', function () {
    $this->planGate->shouldReceive('canAccess')
        ->once()
        ->andReturnTrue();

    $this->client->shouldReceive('dispatch')
        ->with('content_creation', Mockery::type('array'))
        ->once()
        ->andReturn([
            'result' => ['title' => 'AI Generated Title', 'description' => 'Great content'],
            'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
        ]);

    $this->fallback->shouldNotReceive('generateFullContent');

    // Simulate organization context on request
    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateFullContent(
        topic: 'AI Marketing Trends',
        socialNetworks: ['instagram_feed'],
        tone: 'professional',
        keywords: ['ai', 'marketing'],
        language: 'pt-BR',
    );

    expect($result)
        ->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toBe(['title' => 'AI Generated Title', 'description' => 'Great content'])
        ->and($result->tokensInput)->toBe(500)
        ->and($result->costEstimate)->toBe(0.05)
        ->and($result->durationMs)->toBe(3000);
});

it('falls back to Prism when circuit breaker is open', function () {
    $this->planGate->shouldReceive('canAccess')
        ->once()
        ->andReturnTrue();

    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new AiAgentsCircuitOpenException('content_creation'));

    $fallbackResult = new TextGenerationResult(
        output: ['title' => 'Prism Fallback Title'],
        tokensInput: 100,
        tokensOutput: 50,
        model: 'gpt-4o-mini',
        durationMs: 1500,
        costEstimate: 0.01,
    );

    $this->fallback->shouldReceive('generateFullContent')
        ->once()
        ->andReturn($fallbackResult);

    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateFullContent(
        topic: 'AI Marketing Trends',
        socialNetworks: ['instagram_feed'],
    );

    expect($result)
        ->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('gpt-4o-mini')
        ->and($result->output)->toBe(['title' => 'Prism Fallback Title']);
});

it('falls back to Prism on timeout', function () {
    $this->planGate->shouldReceive('canAccess')
        ->once()
        ->andReturnTrue();

    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new AiAgentsTimeoutException('content_creation', 'job-123'));

    $fallbackResult = new TextGenerationResult(
        output: ['title' => 'Prism Timeout Fallback'],
        tokensInput: 80,
        tokensOutput: 40,
        model: 'gpt-4o-mini',
        durationMs: 1200,
        costEstimate: 0.008,
    );

    $this->fallback->shouldReceive('generateFullContent')
        ->once()
        ->andReturn($fallbackResult);

    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateFullContent(
        topic: 'Social Media Strategy',
        socialNetworks: ['tiktok'],
    );

    expect($result)
        ->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('gpt-4o-mini')
        ->and($result->output)->toBe(['title' => 'Prism Timeout Fallback']);
});

it('falls back to Prism when plan does not allow access', function () {
    $this->planGate->shouldReceive('canAccess')
        ->once()
        ->andReturnFalse();

    $this->client->shouldNotReceive('dispatch');

    $fallbackResult = new TextGenerationResult(
        output: ['title' => 'Prism Plan Fallback'],
        tokensInput: 60,
        tokensOutput: 30,
        model: 'gpt-4o-mini',
        durationMs: 900,
        costEstimate: 0.005,
    );

    $this->fallback->shouldReceive('generateFullContent')
        ->once()
        ->andReturn($fallbackResult);

    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateFullContent(
        topic: 'Budget Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result)
        ->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('gpt-4o-mini');
});

it('generates title via LangGraph on happy path', function () {
    $this->planGate->shouldReceive('canAccess')->once()->andReturnTrue();
    $this->client->shouldReceive('dispatch')
        ->with('content_creation', Mockery::type('array'))
        ->once()
        ->andReturn([
            'result' => ['title' => 'AI Generated Title'],
            'metadata' => ['total_tokens' => 200, 'total_cost' => 0.02, 'duration_ms' => 1500],
        ]);
    $this->fallback->shouldNotReceive('generateTitle');
    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateTitle(topic: 'AI Marketing', socialNetwork: 'instagram_feed', tone: 'casual', language: 'en');

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toBe(['title' => 'AI Generated Title']);
});

it('generates description with keywords via LangGraph', function () {
    $this->planGate->shouldReceive('canAccess')->once()->andReturnTrue();
    $this->client->shouldReceive('dispatch')
        ->with('content_creation', Mockery::on(function ($payload) {
            return $payload['keywords'] === ['ai', 'marketing'] && $payload['tone'] === 'casual';
        }))
        ->once()
        ->andReturn([
            'result' => ['description' => 'Great AI content'],
            'metadata' => ['total_tokens' => 300, 'total_cost' => 0.03, 'duration_ms' => 2000],
        ]);
    $this->fallback->shouldNotReceive('generateDescription');
    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateDescription(
        topic: 'AI Marketing', socialNetwork: 'tiktok', tone: 'casual',
        keywords: ['ai', 'marketing'], language: 'en',
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->tokensInput)->toBe(300);
});

it('generates hashtags via LangGraph', function () {
    $this->planGate->shouldReceive('canAccess')->once()->andReturnTrue();
    $this->client->shouldReceive('dispatch')
        ->with('content_creation', Mockery::on(fn ($p) => $p['provider'] === 'instagram_feed'))
        ->once()
        ->andReturn([
            'result' => ['hashtags' => ['#ai', '#marketing']],
            'metadata' => ['total_tokens' => 100, 'total_cost' => 0.01, 'duration_ms' => 800],
        ]);
    $this->fallback->shouldNotReceive('generateHashtags');
    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateHashtags(topic: 'AI Marketing');

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->output)->toBe(['hashtags' => ['#ai', '#marketing']]);
});

it('adapts content across networks via LangGraph', function () {
    $this->planGate->shouldReceive('canAccess')->once()->andReturnTrue();
    $this->client->shouldReceive('dispatch')
        ->with('content_creation', Mockery::on(fn ($p) => str_contains($p['topic'], 'Adapt content cnt-456')))
        ->once()
        ->andReturn([
            'result' => ['adapted' => 'Adapted content for TikTok'],
            'metadata' => ['total_tokens' => 400, 'total_cost' => 0.04, 'duration_ms' => 2500],
        ]);
    $this->fallback->shouldNotReceive('adaptContent');

    $result = $this->adapter->adaptContent(
        contentId: 'cnt-456', organizationId: 'org-123',
        sourceNetwork: 'instagram_feed', targetNetworks: ['tiktok'], preserveTone: true,
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent');
});

it('falls back to Prism on AiAgentsRequestException', function () {
    $this->planGate->shouldReceive('canAccess')->once()->andReturnTrue();
    $this->client->shouldReceive('dispatch')->once()
        ->andThrow(new AiAgentsRequestException('content_creation', 'Server error'));

    $fallbackResult = new TextGenerationResult(
        output: ['title' => 'Prism Request Fallback'], tokensInput: 90, tokensOutput: 45,
        model: 'gpt-4o-mini', durationMs: 1100, costEstimate: 0.009,
    );
    $this->fallback->shouldReceive('generateFullContent')->once()->andReturn($fallbackResult);
    request()->attributes->set('auth_organization_id', 'org-123');

    $result = $this->adapter->generateFullContent(topic: 'Test Topic', socialNetworks: ['instagram_feed']);

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('gpt-4o-mini');
});
