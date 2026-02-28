<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\Analytics\Events\MetricsSynced;
use App\Infrastructure\AIIntelligence\Jobs\ValidatePredictionJob;
use App\Infrastructure\AIIntelligence\Models\PerformancePredictionModel;

/**
 * Triggers immediate prediction validation when metrics are synced.
 *
 * This allows for near real-time validation of predictions
 * as soon as actual metrics become available.
 */
final class TriggerPredictionValidation
{
    public function handle(MetricsSynced $event): void
    {
        // Find pending predictions for this social account
        $predictions = PerformancePredictionModel::query()
            ->where('organization_id', $event->organizationId)
            ->where('social_account_id', $event->socialAccountId)
            ->where('validated_at', null)
            ->where('created_at', '<', now()->subHours(1))
            ->get();

        foreach ($predictions as $prediction) {
            ValidatePredictionJob::dispatch(
                organizationId: $event->organizationId,
                contentId: $prediction->content_id,
                scheduledPostId: $prediction->scheduled_post_id,
                validationType: 'metrics_synced',
            );
        }
    }
}
