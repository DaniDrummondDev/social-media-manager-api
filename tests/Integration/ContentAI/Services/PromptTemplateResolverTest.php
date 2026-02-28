<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Infrastructure\ContentAI\Models\PromptExperimentModel;
use App\Infrastructure\ContentAI\Models\PromptTemplateModel;
use App\Infrastructure\ContentAI\Services\EloquentPromptTemplateResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->orgId = (string) Str::uuid();
    $this->userId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'template-test-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'template-test-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->resolver = new EloquentPromptTemplateResolver();
});

it('resolves default system template when no templates exist', function () {
    $result = $this->resolver->resolve($this->orgId, 'title');

    expect($result->templateId)->toBe('00000000-0000-0000-0000-000000000001')
        ->and($result->experimentId)->toBeNull()
        ->and($result->systemPrompt)->toContain('social media')
        ->and($result->userPromptTemplate)->toContain('{topic}');
});

it('resolves org default template when it exists', function () {
    $templateId = (string) Str::uuid();

    PromptTemplateModel::create([
        'id' => $templateId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 1,
        'name' => 'Default Title Template',
        'system_prompt' => 'Custom system prompt for titles.',
        'user_prompt_template' => 'Generate a title for: {topic}',
        'variables' => ['topic'],
        'is_active' => true,
        'is_default' => true,
        'performance_score' => 0.0,
        'total_uses' => 0,
        'created_by' => $this->userId,
    ]);

    $result = $this->resolver->resolve($this->orgId, 'title');

    expect($result->templateId)->toBe($templateId)
        ->and($result->systemPrompt)->toBe('Custom system prompt for titles.')
        ->and($result->userPromptTemplate)->toBe('Generate a title for: {topic}');
});

it('resolves highest performance template when min uses reached', function () {
    $lowPerfId = (string) Str::uuid();
    $highPerfId = (string) Str::uuid();

    PromptTemplateModel::create([
        'id' => $lowPerfId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 1,
        'name' => 'Low Performance Template',
        'system_prompt' => 'Low perf prompt.',
        'user_prompt_template' => 'Low: {topic}',
        'variables' => ['topic'],
        'is_active' => true,
        'is_default' => false,
        'performance_score' => 0.5,
        'total_uses' => 25,
        'created_by' => $this->userId,
    ]);

    PromptTemplateModel::create([
        'id' => $highPerfId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 2, // Different version to avoid unique constraint
        'name' => 'High Performance Template',
        'system_prompt' => 'High perf prompt.',
        'user_prompt_template' => 'High: {topic}',
        'variables' => ['topic'],
        'is_active' => true,
        'is_default' => false,
        'performance_score' => 0.9,
        'total_uses' => 30,
        'created_by' => $this->userId,
    ]);

    $result = $this->resolver->resolve($this->orgId, 'title');

    expect($result->templateId)->toBe($highPerfId)
        ->and($result->systemPrompt)->toBe('High perf prompt.');
});

it('routes to experiment variant based on traffic split', function () {
    $variantAId = (string) Str::uuid();
    $variantBId = (string) Str::uuid();
    $experimentId = (string) Str::uuid();

    PromptTemplateModel::create([
        'id' => $variantAId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 1,
        'name' => 'Variant A',
        'system_prompt' => 'Variant A prompt.',
        'user_prompt_template' => 'A: {topic}',
        'variables' => ['topic'],
        'is_active' => true,
        'is_default' => false,
        'performance_score' => 0.0,
        'total_uses' => 0,
        'created_by' => $this->userId,
    ]);

    PromptTemplateModel::create([
        'id' => $variantBId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'version' => 2, // Different version to avoid unique constraint
        'name' => 'Variant B',
        'system_prompt' => 'Variant B prompt.',
        'user_prompt_template' => 'B: {topic}',
        'variables' => ['topic'],
        'is_active' => true,
        'is_default' => false,
        'performance_score' => 0.0,
        'total_uses' => 0,
        'created_by' => $this->userId,
    ]);

    PromptExperimentModel::create([
        'id' => $experimentId,
        'organization_id' => $this->orgId,
        'generation_type' => 'title',
        'name' => 'Test Experiment',
        'status' => 'running',
        'variant_a_id' => $variantAId,
        'variant_b_id' => $variantBId,
        'traffic_split' => 0.5,
        'min_sample_size' => 100,
        'variant_a_uses' => 0,
        'variant_a_accepted' => 0,
        'variant_b_uses' => 0,
        'variant_b_accepted' => 0,
        'started_at' => now(),
    ]);

    $result = $this->resolver->resolve($this->orgId, 'title');

    expect($result->experimentId)->toBe($experimentId)
        ->and($result->variantSelected)->toBeIn(['A', 'B'])
        ->and($result->templateId)->toBeIn([$variantAId, $variantBId]);
});

it('resolves provider from container', function () {
    $resolver = app(PromptTemplateResolverInterface::class);

    expect($resolver)->toBeInstanceOf(EloquentPromptTemplateResolver::class);
});
