<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ValidatePredictionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $contentId,
        public readonly string $scheduledPostId,
        public readonly string $validationType,
    ) {
        $this->onQueue('ai-intelligence');
    }

    public function handle(PredictionValidatorInterface $validator): void
    {
        Log::info('ValidatePredictionJob: Starting validation', [
            'organization_id' => $this->organizationId,
            'content_id' => $this->contentId,
            'scheduled_post_id' => $this->scheduledPostId,
            'validation_type' => $this->validationType,
        ]);

        try {
            $validator->validate(
                $this->organizationId,
                $this->contentId,
                $this->scheduledPostId,
            );

            Log::info('ValidatePredictionJob: Validation completed', [
                'organization_id' => $this->organizationId,
                'content_id' => $this->contentId,
            ]);
        } catch (\Throwable $e) {
            Log::error('ValidatePredictionJob: Validation failed', [
                'organization_id' => $this->organizationId,
                'content_id' => $this->contentId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function tags(): array
    {
        return [
            'organization:'.$this->organizationId,
            'content:'.$this->contentId,
            'type:validate_prediction',
        ];
    }
}
