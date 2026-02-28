<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\DTOs\ResolvedPromptResult;
use App\Infrastructure\ContentAI\Models\PromptExperimentModel;
use App\Infrastructure\ContentAI\Models\PromptTemplateModel;

final class EloquentPromptTemplateResolver implements PromptTemplateResolverInterface
{
    private const MIN_USES_FOR_PERFORMANCE_RANKING = 20;

    public function resolve(string $organizationId, string $generationType): ResolvedPromptResult
    {
        // Priority 1: Check for active A/B experiment
        $experimentResult = $this->resolveFromExperiment($organizationId, $generationType);
        if ($experimentResult !== null) {
            return $experimentResult;
        }

        // Priority 2: Template with highest performance_score (min 20 uses)
        $performanceResult = $this->resolveByPerformance($organizationId, $generationType);
        if ($performanceResult !== null) {
            return $performanceResult;
        }

        // Priority 3: Default template for org (is_default = true)
        $defaultResult = $this->resolveOrgDefault($organizationId, $generationType);
        if ($defaultResult !== null) {
            return $defaultResult;
        }

        // Priority 4: Global system template (organization_id = NULL)
        return $this->resolveGlobalTemplate($generationType);
    }

    private function resolveFromExperiment(string $organizationId, string $generationType): ?ResolvedPromptResult
    {
        /** @var PromptExperimentModel|null $experiment */
        $experiment = PromptExperimentModel::query()
            ->where('organization_id', $organizationId)
            ->where('generation_type', $generationType)
            ->where('status', 'running')
            ->first();

        if ($experiment === null) {
            return null;
        }

        // Route by traffic_split (random selection based on split percentage)
        $random = mt_rand(1, 100) / 100;
        $selectedVariantId = $random <= $experiment->traffic_split
            ? $experiment->variant_a_id
            : $experiment->variant_b_id;

        $variantSelected = $selectedVariantId === $experiment->variant_a_id ? 'A' : 'B';

        /** @var PromptTemplateModel|null $template */
        $template = PromptTemplateModel::find($selectedVariantId);

        if ($template === null) {
            return null;
        }

        return new ResolvedPromptResult(
            templateId: $template->id,
            experimentId: $experiment->id,
            systemPrompt: $template->system_prompt,
            userPromptTemplate: $template->user_prompt_template,
            variables: $template->variables ?? [],
            variantSelected: $variantSelected,
        );
    }

    private function resolveByPerformance(string $organizationId, string $generationType): ?ResolvedPromptResult
    {
        /** @var PromptTemplateModel|null $template */
        $template = PromptTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->where('generation_type', $generationType)
            ->where('is_active', true)
            ->where('total_uses', '>=', self::MIN_USES_FOR_PERFORMANCE_RANKING)
            ->orderByDesc('performance_score')
            ->first();

        if ($template === null) {
            return null;
        }

        return $this->buildResult($template);
    }

    private function resolveOrgDefault(string $organizationId, string $generationType): ?ResolvedPromptResult
    {
        /** @var PromptTemplateModel|null $template */
        $template = PromptTemplateModel::query()
            ->where('organization_id', $organizationId)
            ->where('generation_type', $generationType)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($template === null) {
            return null;
        }

        return $this->buildResult($template);
    }

    private function resolveGlobalTemplate(string $generationType): ResolvedPromptResult
    {
        /** @var PromptTemplateModel|null $template */
        $template = PromptTemplateModel::query()
            ->whereNull('organization_id')
            ->where('generation_type', $generationType)
            ->where('is_active', true)
            ->first();

        if ($template !== null) {
            return $this->buildResult($template);
        }

        // Ultimate fallback: Return a system-generated template
        return $this->buildDefaultSystemTemplate($generationType);
    }

    private function buildResult(PromptTemplateModel $template): ResolvedPromptResult
    {
        return new ResolvedPromptResult(
            templateId: $template->id,
            experimentId: null,
            systemPrompt: $template->system_prompt,
            userPromptTemplate: $template->user_prompt_template,
            variables: $template->variables ?? [],
            variantSelected: null,
        );
    }

    private function buildDefaultSystemTemplate(string $generationType): ResolvedPromptResult
    {
        $systemPrompts = [
            'title' => 'You are a social media content specialist. Generate compelling titles. Return as a JSON array of objects with "title", "character_count", and "tone" fields.',
            'description' => 'You are a social media copywriter. Generate engaging descriptions/captions optimized for social media platforms. Return as a JSON object with "description", "character_count", and "max_characters" fields.',
            'hashtags' => 'You are a social media hashtag specialist. Generate relevant hashtags as a JSON array of objects with "tag" (without #) and "competition" (high/medium/low) fields.',
            'full_content' => 'You are a cross-platform social media content creator. Generate comprehensive content adapted for different platforms.',
        ];

        $userPromptTemplates = [
            'title' => 'Generate 3 title suggestions for the following topic: {topic}. Social network: {social_network}. Tone: {tone}.',
            'description' => 'Generate a description/caption for: {topic}. Social network: {social_network}. Tone: {tone}.',
            'hashtags' => 'Generate 10 relevant hashtags for: {topic}. Niche: {niche}. Platform: {social_network}.',
            'full_content' => 'Generate full social media content for: {topic}. Networks: {social_networks}. Keywords: {keywords}.',
        ];

        return new ResolvedPromptResult(
            templateId: '00000000-0000-0000-0000-000000000001',
            experimentId: null,
            systemPrompt: $systemPrompts[$generationType] ?? "You are a social media content expert. Generate {$generationType} content.",
            userPromptTemplate: $userPromptTemplates[$generationType] ?? 'Generate a {generation_type} about {topic} for {social_network}.',
            variables: ['generation_type', 'topic', 'social_network', 'tone', 'niche', 'keywords', 'social_networks'],
            variantSelected: null,
        );
    }
}
