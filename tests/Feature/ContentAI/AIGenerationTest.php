<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // AI Generation requires subscription with ai_generations quota
    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    // Mock TextGeneratorInterface
    $this->mockGenerator = Mockery::mock(TextGeneratorInterface::class);
    $this->app->instance(TextGeneratorInterface::class, $this->mockGenerator);
});

it('generates title — 200', function () {
    $this->mockGenerator->shouldReceive('generateTitle')->once()->andReturn(
        new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Generated Title', 'character_count' => 15, 'tone' => 'professional']]],
            tokensInput: 120,
            tokensOutput: 85,
            model: 'gpt-4o',
            durationMs: 1200,
            costEstimate: 0.003,
        ),
    );

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
        'social_network' => 'instagram',
        'tone' => 'professional',
        'language' => 'pt_BR',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'title')
        ->assertJsonPath('data.attributes.model', 'gpt-4o')
        ->assertJsonPath('data.attributes.tokens_input', 120);
});

it('generates description — 200', function () {
    $this->mockGenerator->shouldReceive('generateDescription')->once()->andReturn(
        new TextGenerationResult(
            output: ['description' => 'Generated description text', 'character_count' => 27],
            tokensInput: 150,
            tokensOutput: 120,
            model: 'gpt-4o',
            durationMs: 1500,
            costEstimate: 0.005,
        ),
    );

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-description', [
        'topic' => 'Black Friday promotion for fashion store',
        'keywords' => ['desconto', 'moda'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'description');
});

it('generates hashtags — 200', function () {
    $this->mockGenerator->shouldReceive('generateHashtags')->once()->andReturn(
        new TextGenerationResult(
            output: ['hashtags' => [['tag' => 'blackfriday', 'competition' => 'high']]],
            tokensInput: 80,
            tokensOutput: 60,
            model: 'gpt-4o-mini',
            durationMs: 800,
            costEstimate: 0.001,
        ),
    );

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-hashtags', [
        'topic' => 'Black Friday fashion promotion deals',
        'niche' => 'moda feminina',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'hashtags');
});

it('generates full content — 200', function () {
    $this->mockGenerator->shouldReceive('generateFullContent')->once()->andReturn(
        new TextGenerationResult(
            output: ['content_per_network' => ['instagram' => ['title' => 'IG title']]],
            tokensInput: 250,
            tokensOutput: 400,
            model: 'gpt-4o',
            durationMs: 3000,
            costEstimate: 0.01,
        ),
    );

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-content', [
        'topic' => 'Spring collection launch for fashion brand',
        'social_networks' => ['instagram', 'tiktok'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'full_content');
});

it('rejects invalid topic — 422', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'short', // less than 10 chars
    ]);

    $response->assertStatus(422);
});

it('rejects unauthenticated — 401', function () {
    $response = $this->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
    ]);

    $response->assertStatus(401);
});
