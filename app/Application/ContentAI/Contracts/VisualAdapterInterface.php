<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Contracts;

use App\Application\ContentAI\DTOs\VisualAdaptationResult;

interface VisualAdapterInterface
{
    /**
     * Adapt an image for multiple social networks via multi-agent Visual Adaptation pipeline.
     *
     * @param  list<string>  $targetNetworks
     * @param  array<string, mixed>|null  $brandGuidelines
     */
    public function adaptImage(
        string $organizationId,
        string $imageUrl,
        array $targetNetworks,
        ?array $brandGuidelines = null,
    ): VisualAdaptationResult;
}
