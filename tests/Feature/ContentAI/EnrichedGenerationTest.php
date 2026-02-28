<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Infrastructure\AIIntelligence\Models\OrgStyleProfileModel;
use App\Infrastructure\ContentAI\Models\PromptTemplateModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'suggestions' => [
                        ['title' => 'Amazing Productivity Tips', 'character_count' => 24, 'tone' => 'engaging'],
                        ['title' => 'Boost Your Workflow', 'character_count' => 19, 'tone' => 'professional'],
                    ],
                ])]],
            ],
            'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 80],
        ]),
    ]);

    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'feature-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'feature-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('generates title with full enrichment pipeline', function () {
    // Create a custom template
    PromptTemplateModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 1,
        'name' => 'Custom Title Template',
        'system_prompt' => 'You are a creative social media expert specializing in engaging titles.',
        'user_prompt_template' => 'Create 3 title options for: {topic}. Tone: {tone}. Platform: {social_network}.',
        'variables' => ['topic', 'tone', 'social_network'],
        'is_active' => true,
        'is_default' => true,
        'performance_score' => 0.0,
        'total_uses' => 0,
        'created_by' => $this->userId,
    ]);

    // Create a style profile
    OrgStyleProfileModel::create([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'sample_size' => 25,
        'tone_preferences' => ['preferred' => 'engaging'],
        'length_preferences' => ['avg_preferred_length' => 60],
        'vocabulary_preferences' => [],
        'structure_preferences' => ['uses_emojis' => false],
        'hashtag_preferences' => [],
        'style_summary' => 'Engaging and concise.',
        'generated_at' => now(),
        'expires_at' => now()->addDays(7),
    ]);

    /** @var TextGeneratorInterface $generator */
    $generator = app(TextGeneratorInterface::class);

    $result = $generator->generateTitle(
        topic: 'productivity tips for remote workers',
        socialNetwork: 'instagram',
        tone: 'engaging',
        language: 'en-US',
        organizationId: $this->orgId,
    );

    expect($result->output)->toHaveKey('suggestions')
        ->and($result->tokensInput)->toBeGreaterThan(0)
        ->and($result->tokensOutput)->toBeGreaterThan(0);
});

it('generates description with enrichment', function () {
    /** @var TextGeneratorInterface $generator */
    $generator = app(TextGeneratorInterface::class);

    $result = $generator->generateDescription(
        topic: 'productivity tips',
        socialNetwork: 'instagram',
        tone: 'professional',
        keywords: ['productivity', 'remote work'],
        language: 'en-US',
        organizationId: $this->orgId,
    );

    // The HTTP mock from beforeEach returns 'suggestions' format
    expect($result->output)->not->toBeEmpty()
        ->and($result->tokensInput)->toBeGreaterThan(0);
});

it('generates hashtags with enrichment', function () {
    /** @var TextGeneratorInterface $generator */
    $generator = app(TextGeneratorInterface::class);

    $result = $generator->generateHashtags(
        topic: 'productivity tips',
        niche: 'business',
        socialNetwork: 'instagram',
        organizationId: $this->orgId,
    );

    // The HTTP mock from beforeEach returns 'suggestions' format
    expect($result->output)->not->toBeEmpty();
});

it('gracefully handles missing enrichment data', function () {
    /** @var TextGeneratorInterface $generator */
    $generator = app(TextGeneratorInterface::class);

    // No templates, no style profiles, no audience insights
    // Should still work with defaults

    $result = $generator->generateTitle(
        topic: 'productivity tips',
        socialNetwork: 'instagram',
        organizationId: $this->orgId,
    );

    expect($result->output)->toHaveKey('suggestions');
});
