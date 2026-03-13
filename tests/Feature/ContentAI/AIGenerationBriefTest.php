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

it('generates title with fields_only mode (default, backward compatible) — 200', function () {
    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => $topic === 'Black Friday promotion for fashion store')
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Generated Title']]],
            tokensInput: 120,
            tokensOutput: 85,
            model: 'gpt-4o',
            durationMs: 1200,
            costEstimate: 0.003,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
        'social_network' => 'instagram',
        'tone' => 'professional',
        'language' => 'pt_BR',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'title')
        ->assertJsonPath('data.attributes.model', 'gpt-4o');
});

it('generates title with brief_only mode — topic contains campaign brief header — 200', function () {
    $campaignId = (string) Str::uuid();

    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Test Campaign',
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => 'Campaign for Black Friday',
        'brief_target_audience' => 'Young adults 18-30',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => str_contains($topic, '[CAMPAIGN BRIEF]')
            && str_contains($topic, 'Campaign for Black Friday')
            && str_contains($topic, 'Young adults 18-30')
        )
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Brief-Based Title']]],
            tokensInput: 120,
            tokensOutput: 85,
            model: 'gpt-4o',
            durationMs: 1200,
            costEstimate: 0.003,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'title');
});

it('generates title with brief_and_fields mode — topic contains brief and user topic — 200', function () {
    $campaignId = (string) Str::uuid();

    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Test Campaign',
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => 'Campaign for Black Friday',
        'brief_target_audience' => 'Young adults 18-30',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => str_contains($topic, '[CAMPAIGN BRIEF]')
            && str_contains($topic, '[USER TOPIC]')
            && str_contains($topic, 'Black Friday promotion for fashion store')
        )
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Combined Title']]],
            tokensInput: 120,
            tokensOutput: 85,
            model: 'gpt-4o',
            durationMs: 1200,
            costEstimate: 0.003,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
        'generation_mode' => 'brief_and_fields',
        'campaign_id' => $campaignId,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'title');
});

it('returns 422 when brief_only mode is used without campaign_id', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
        'generation_mode' => 'brief_only',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR')
        ->assertJsonPath('errors.0.field', 'campaign_id');
});

it('returns 422 when brief_only mode is used with campaign that has no brief', function () {
    $campaignId = (string) Str::uuid();

    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $this->orgId,
        'created_by' => $this->user['id'],
        'name' => 'Campaign Without Brief',
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => null,
        'brief_target_audience' => null,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.0.code', 'CAMPAIGN_BRIEF_REQUIRED');
});

it('returns 404 when brief_only mode is used with campaign from another organization', function () {
    // Create another org with another user
    $otherUser = $this->createUserInDb();
    $otherOrgData = $this->createOrgWithOwner($otherUser['id']);
    $otherOrgId = $otherOrgData['org']['id'];

    $campaignId = (string) Str::uuid();

    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $otherOrgId,
        'created_by' => $otherUser['id'],
        'name' => 'Other Org Campaign',
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => 'Other org brief',
        'brief_target_audience' => 'Other audience',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('errors.0.code', 'CAMPAIGN_NOT_FOUND');
});
