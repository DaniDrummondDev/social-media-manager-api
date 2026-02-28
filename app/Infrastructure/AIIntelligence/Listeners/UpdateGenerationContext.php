<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\AIIntelligence\Events\OrgStyleProfileGenerated;
use App\Infrastructure\AIIntelligence\Jobs\UpdateAIGenerationContextJob;

/**
 * Dispatches a job to update the AI generation context when
 * a new style profile is generated for an organization.
 *
 * This ensures that subsequent content generations use the
 * latest learned style preferences.
 */
final class UpdateGenerationContext
{
    public function handle(OrgStyleProfileGenerated $event): void
    {
        UpdateAIGenerationContextJob::dispatch(
            organizationId: $event->organizationId,
            contextType: "style_profile:{$event->generationType}",
        );
    }
}
