<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\DTOs\ResolvedPromptResult;

final class StubPromptTemplateResolver implements PromptTemplateResolverInterface
{
    public function resolve(string $organizationId, string $generationType): ResolvedPromptResult
    {
        return new ResolvedPromptResult(
            templateId: '00000000-0000-0000-0000-000000000001',
            experimentId: null,
            systemPrompt: "You are a social media content expert. Generate {$generationType} content.",
            userPromptTemplate: 'Generate a {generation_type} about {topic} for {social_network}.',
            variables: ['generation_type', 'topic', 'social_network'],
            variantSelected: null,
        );
    }
}
