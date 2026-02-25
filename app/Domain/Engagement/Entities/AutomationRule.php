<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AutomationRule
{
    /**
     * @param  array<RuleCondition>  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $name,
        public int $priority,
        public array $conditions,
        public ActionType $actionType,
        public ?string $responseTemplate,
        public ?Uuid $webhookId,
        public int $delaySeconds,
        public int $dailyLimit,
        public bool $isActive,
        public ?array $appliesToNetworks,
        public ?array $appliesToCampaigns,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<RuleCondition>  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public static function create(
        Uuid $organizationId,
        string $name,
        int $priority,
        array $conditions,
        ActionType $actionType,
        ?string $responseTemplate = null,
        ?Uuid $webhookId = null,
        int $delaySeconds = 120,
        int $dailyLimit = 100,
        ?array $appliesToNetworks = null,
        ?array $appliesToCampaigns = null,
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            name: $name,
            priority: $priority,
            conditions: $conditions,
            actionType: $actionType,
            responseTemplate: $responseTemplate,
            webhookId: $webhookId,
            delaySeconds: $delaySeconds,
            dailyLimit: $dailyLimit,
            isActive: true,
            appliesToNetworks: $appliesToNetworks,
            appliesToCampaigns: $appliesToCampaigns,
            deletedAt: null,
            purgeAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param  array<RuleCondition>  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $name,
        int $priority,
        array $conditions,
        ActionType $actionType,
        ?string $responseTemplate,
        ?Uuid $webhookId,
        int $delaySeconds,
        int $dailyLimit,
        bool $isActive,
        ?array $appliesToNetworks,
        ?array $appliesToCampaigns,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            priority: $priority,
            conditions: $conditions,
            actionType: $actionType,
            responseTemplate: $responseTemplate,
            webhookId: $webhookId,
            delaySeconds: $delaySeconds,
            dailyLimit: $dailyLimit,
            isActive: $isActive,
            appliesToNetworks: $appliesToNetworks,
            appliesToCampaigns: $appliesToCampaigns,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param  array<RuleCondition>|null  $conditions
     * @param  array<string>|null  $appliesToNetworks
     * @param  array<string>|null  $appliesToCampaigns
     */
    public function update(
        ?string $name = null,
        ?int $priority = null,
        ?array $conditions = null,
        ?ActionType $actionType = null,
        ?string $responseTemplate = null,
        ?Uuid $webhookId = null,
        ?int $delaySeconds = null,
        ?int $dailyLimit = null,
        ?array $appliesToNetworks = null,
        ?array $appliesToCampaigns = null,
    ): self {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $name ?? $this->name,
            priority: $priority ?? $this->priority,
            conditions: $conditions ?? $this->conditions,
            actionType: $actionType ?? $this->actionType,
            responseTemplate: $responseTemplate ?? $this->responseTemplate,
            webhookId: $webhookId ?? $this->webhookId,
            delaySeconds: $delaySeconds ?? $this->delaySeconds,
            dailyLimit: $dailyLimit ?? $this->dailyLimit,
            isActive: $this->isActive,
            appliesToNetworks: $appliesToNetworks ?? $this->appliesToNetworks,
            appliesToCampaigns: $appliesToCampaigns ?? $this->appliesToCampaigns,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            priority: $this->priority,
            conditions: $this->conditions,
            actionType: $this->actionType,
            responseTemplate: $this->responseTemplate,
            webhookId: $this->webhookId,
            delaySeconds: $this->delaySeconds,
            dailyLimit: $this->dailyLimit,
            isActive: true,
            appliesToNetworks: $this->appliesToNetworks,
            appliesToCampaigns: $this->appliesToCampaigns,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            priority: $this->priority,
            conditions: $this->conditions,
            actionType: $this->actionType,
            responseTemplate: $this->responseTemplate,
            webhookId: $this->webhookId,
            delaySeconds: $this->delaySeconds,
            dailyLimit: $this->dailyLimit,
            isActive: false,
            appliesToNetworks: $this->appliesToNetworks,
            appliesToCampaigns: $this->appliesToCampaigns,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function softDelete(): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            name: $this->name,
            priority: $this->priority,
            conditions: $this->conditions,
            actionType: $this->actionType,
            responseTemplate: $this->responseTemplate,
            webhookId: $this->webhookId,
            delaySeconds: $this->delaySeconds,
            dailyLimit: $this->dailyLimit,
            isActive: false,
            appliesToNetworks: $this->appliesToNetworks,
            appliesToCampaigns: $this->appliesToCampaigns,
            deletedAt: $now,
            purgeAt: $now->modify('+30 days'),
            createdAt: $this->createdAt,
            updatedAt: $now,
        );
    }

    public function evaluateConditions(Comment $comment): bool
    {
        if ($this->conditions === []) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (! $condition->evaluate($comment->text, $comment->sentiment, $comment->authorName)) {
                return false;
            }
        }

        return true;
    }

    public function matchesFilters(string $provider, ?string $campaignId = null): bool
    {
        if ($this->appliesToNetworks !== null && ! in_array($provider, $this->appliesToNetworks, true)) {
            return false;
        }

        if ($this->appliesToCampaigns !== null && $campaignId !== null && ! in_array($campaignId, $this->appliesToCampaigns, true)) {
            return false;
        }

        return true;
    }
}
