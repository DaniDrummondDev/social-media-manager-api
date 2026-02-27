<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

final readonly class PlanLimits
{
    private function __construct(
        public int $members,
        public int $socialAccounts,
        public int $publicationsMonth,
        public int $aiGenerationsMonth,
        public int $storageGb,
        public int $activeCampaigns,
        public int $automations,
        public int $webhooks,
        public int $crmConnections,
        public int $reportsMonth,
        public int $analyticsRetentionDays,
        public int $paidAdvertising,
    ) {}

    /**
     * @param  array<string, int>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            members: $data['members'] ?? 1,
            socialAccounts: $data['social_accounts'] ?? 3,
            publicationsMonth: $data['publications_month'] ?? 30,
            aiGenerationsMonth: $data['ai_generations_month'] ?? 50,
            storageGb: $data['storage_gb'] ?? 1,
            activeCampaigns: $data['active_campaigns'] ?? 2,
            automations: $data['automations'] ?? 0,
            webhooks: $data['webhooks'] ?? 0,
            crmConnections: $data['crm_connections'] ?? 0,
            reportsMonth: $data['reports_month'] ?? 5,
            analyticsRetentionDays: $data['analytics_retention_days'] ?? 30,
            paidAdvertising: $data['paid_advertising'] ?? 0,
        );
    }

    public function getLimit(UsageResourceType $resource): int
    {
        return match ($resource) {
            UsageResourceType::Publications => $this->publicationsMonth,
            UsageResourceType::AiGenerations => $this->aiGenerationsMonth,
            UsageResourceType::StorageBytes => $this->storageGb === -1 ? -1 : $this->storageGb * 1_073_741_824,
            UsageResourceType::Members => $this->members,
            UsageResourceType::SocialAccounts => $this->socialAccounts,
            UsageResourceType::Campaigns => $this->activeCampaigns,
            UsageResourceType::Automations => $this->automations,
            UsageResourceType::Webhooks => $this->webhooks,
            UsageResourceType::Reports => $this->reportsMonth,
            UsageResourceType::PaidAdvertising => $this->paidAdvertising,
        };
    }

    public function isUnlimited(UsageResourceType $resource): bool
    {
        return $this->getLimit($resource) === -1;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'members' => $this->members,
            'social_accounts' => $this->socialAccounts,
            'publications_month' => $this->publicationsMonth,
            'ai_generations_month' => $this->aiGenerationsMonth,
            'storage_gb' => $this->storageGb,
            'active_campaigns' => $this->activeCampaigns,
            'automations' => $this->automations,
            'webhooks' => $this->webhooks,
            'crm_connections' => $this->crmConnections,
            'reports_month' => $this->reportsMonth,
            'analytics_retention_days' => $this->analyticsRetentionDays,
            'paid_advertising' => $this->paidAdvertising,
        ];
    }
}
