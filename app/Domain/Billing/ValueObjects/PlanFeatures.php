<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

final readonly class PlanFeatures
{
    private function __construct(
        public bool $aiGenerationBasic,
        public bool $aiGenerationAdvanced,
        public bool $aiIntelligence,
        public bool $aiLearning,
        public bool $automations,
        public bool $webhooks,
        public bool $crmNative,
        public bool $exportPdf,
        public bool $exportCsv,
        public bool $priorityPublishing,
    ) {}

    /**
     * @param  array<string, bool>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            aiGenerationBasic: $data['ai_generation_basic'] ?? false,
            aiGenerationAdvanced: $data['ai_generation_advanced'] ?? false,
            aiIntelligence: $data['ai_intelligence'] ?? false,
            aiLearning: $data['ai_learning'] ?? false,
            automations: $data['automations'] ?? false,
            webhooks: $data['webhooks'] ?? false,
            crmNative: $data['crm_native'] ?? false,
            exportPdf: $data['export_pdf'] ?? false,
            exportCsv: $data['export_csv'] ?? false,
            priorityPublishing: $data['priority_publishing'] ?? false,
        );
    }

    public function hasFeature(string $featureKey): bool
    {
        return match ($featureKey) {
            'ai_generation_basic' => $this->aiGenerationBasic,
            'ai_generation_advanced' => $this->aiGenerationAdvanced,
            'ai_intelligence' => $this->aiIntelligence,
            'ai_learning' => $this->aiLearning,
            'automations' => $this->automations,
            'webhooks' => $this->webhooks,
            'crm_native' => $this->crmNative,
            'export_pdf' => $this->exportPdf,
            'export_csv' => $this->exportCsv,
            'priority_publishing' => $this->priorityPublishing,
            default => false,
        };
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'ai_generation_basic' => $this->aiGenerationBasic,
            'ai_generation_advanced' => $this->aiGenerationAdvanced,
            'ai_intelligence' => $this->aiIntelligence,
            'ai_learning' => $this->aiLearning,
            'automations' => $this->automations,
            'webhooks' => $this->webhooks,
            'crm_native' => $this->crmNative,
            'export_pdf' => $this->exportPdf,
            'export_csv' => $this->exportCsv,
            'priority_publishing' => $this->priorityPublishing,
        ];
    }
}
