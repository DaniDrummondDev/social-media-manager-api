<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Listeners;

use App\Domain\ContentAI\Events\PromptExperimentCompleted;
use App\Infrastructure\ContentAI\Models\PromptExperimentModel;
use App\Infrastructure\ContentAI\Models\PromptTemplateModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Activates the winning template when a prompt experiment completes.
 *
 * This is a synchronous operation that:
 * 1. Updates the winning template to is_default = true
 * 2. Marks the experiment as completed with winner_id
 * 3. Deactivates the losing template if configured
 */
final class ActivateWinningTemplate
{
    public function handle(PromptExperimentCompleted $event): void
    {
        Log::info('ActivateWinningTemplate: Processing experiment completion', [
            'experiment_id' => $event->aggregateId,
            'organization_id' => $event->organizationId,
            'winner_id' => $event->winnerId,
            'confidence_level' => $event->confidenceLevel,
        ]);

        DB::transaction(function () use ($event) {
            /** @var PromptExperimentModel|null $experiment */
            $experiment = PromptExperimentModel::find($event->aggregateId);

            if ($experiment === null) {
                Log::warning('ActivateWinningTemplate: Experiment not found', [
                    'experiment_id' => $event->aggregateId,
                ]);

                return;
            }

            // Deactivate all default templates for this generation type
            PromptTemplateModel::query()
                ->where('organization_id', $event->organizationId)
                ->where('generation_type', $experiment->generation_type)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            // Activate the winning template
            PromptTemplateModel::query()
                ->where('id', $event->winnerId)
                ->update([
                    'is_default' => true,
                    'is_active' => true,
                ]);

            // Update the experiment status
            $experiment->update([
                'status' => 'completed',
                'winner_id' => $event->winnerId,
                'confidence_level' => $event->confidenceLevel,
                'completed_at' => now(),
            ]);

            Log::info('ActivateWinningTemplate: Template activated successfully', [
                'experiment_id' => $event->aggregateId,
                'winner_id' => $event->winnerId,
            ]);
        });
    }
}
