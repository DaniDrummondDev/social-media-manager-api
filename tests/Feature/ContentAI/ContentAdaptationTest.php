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
    $this->orgId = $this->createOrgWithOwner($this->user['id'])['org']['id'];
    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    // Content adaptation requires Professional plan with ai_generation_advanced
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

it('POST /ai/adapt-content — 200 with AIGeneration resource', function () {
    $this->mockGenerator->shouldReceive('adaptContent')->once()->andReturn(
        new TextGenerationResult(
            output: [
                'adaptations' => [
                    'tiktok' => ['title' => 'TikTok Title', 'description' => 'Adapted for TikTok'],
                    'youtube' => ['title' => 'YouTube Title', 'description' => 'Adapted for YouTube'],
                ],
            ],
            tokensInput: 200,
            tokensOutput: 350,
            model: 'gpt-4o',
            durationMs: 2500,
            costEstimate: 0.008,
        ),
    );

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/adapt-content', [
        'content_id' => Str::uuid()->toString(),
        'source_network' => 'instagram',
        'target_networks' => ['tiktok', 'youtube'],
        'preserve_tone' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'cross_network_adaptation')
        ->assertJsonPath('data.attributes.model', 'gpt-4o')
        ->assertJsonPath('data.attributes.tokens_input', 200);
});

it('POST /ai/adapt-content — 422 missing required fields', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/adapt-content', []);

    $response->assertStatus(422);
});

it('POST /ai/adapt-content — 422 invalid source_network', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/adapt-content', [
        'content_id' => Str::uuid()->toString(),
        'source_network' => 'facebook',
        'target_networks' => ['tiktok'],
    ]);

    $response->assertStatus(422);
});

it('POST /ai/adapt-content — 401 unauthenticated', function () {
    $response = $this->postJson('/api/v1/ai/adapt-content', [
        'content_id' => Str::uuid()->toString(),
        'source_network' => 'instagram',
        'target_networks' => ['tiktok'],
    ]);

    $response->assertStatus(401);
});
