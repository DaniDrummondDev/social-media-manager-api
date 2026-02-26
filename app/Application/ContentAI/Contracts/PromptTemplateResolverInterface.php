<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Contracts;

use App\Application\ContentAI\DTOs\ResolvedPromptResult;

interface PromptTemplateResolverInterface
{
    /**
     * Resolve the best prompt template for a generation request.
     *
     * Priority chain (RN-ALL-23):
     * 1. Active A/B experiment → route by traffic_split
     * 2. Template with highest performance_score (min 20 uses)
     * 3. Default template for org (is_default = true)
     * 4. Global system template (organization_id = NULL)
     *
     * This method NEVER returns null — always falls back to global system template.
     */
    public function resolve(string $organizationId, string $generationType): ResolvedPromptResult;
}
