<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Jobs;

use App\Application\AIIntelligence\DTOs\AttributeCrmConversionInput;
use App\Application\AIIntelligence\UseCases\AttributeCrmConversionUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AttributeCrmConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 120, 300];

    public readonly string $correlationId;

    public readonly string $traceId;

    /**
     * @param  array<string, mixed>  $interactionData
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $crmConnectionId,
        public readonly string $contentId,
        public readonly string $crmEntityType,
        public readonly string $crmEntityId,
        public readonly string $attributionType,
        public readonly ?float $attributionValue = null,
        public readonly ?string $currency = null,
        public readonly ?string $crmStage = null,
        public readonly array $interactionData = [],
    ) {
        $this->onQueue('ai-intelligence');
        $this->correlationId = (string) Str::uuid();
        $this->traceId = (string) Str::uuid();
    }

    public function handle(AttributeCrmConversionUseCase $useCase): void
    {
        Log::info('AttributeCrmConversionJob: Attributing CRM conversion.', [
            'organization_id' => $this->organizationId,
            'crm_entity_type' => $this->crmEntityType,
            'attribution_type' => $this->attributionType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
        ]);

        $useCase->execute(new AttributeCrmConversionInput(
            organizationId: $this->organizationId,
            userId: $this->userId,
            crmConnectionId: $this->crmConnectionId,
            contentId: $this->contentId,
            crmEntityType: $this->crmEntityType,
            crmEntityId: $this->crmEntityId,
            attributionType: $this->attributionType,
            attributionValue: $this->attributionValue,
            currency: $this->currency,
            crmStage: $this->crmStage,
            interactionData: $this->interactionData,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AttributeCrmConversionJob: Failed.', [
            'organization_id' => $this->organizationId,
            'crm_entity_type' => $this->crmEntityType,
            'correlation_id' => $this->correlationId,
            'trace_id' => $this->traceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
