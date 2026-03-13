<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Shared\ValueObjects\Uuid;

interface AutomationRuleRepositoryInterface
{
    public function create(AutomationRule $rule): void;

    public function update(AutomationRule $rule): void;

    public function findById(Uuid $id): ?AutomationRule;

    /**
     * @return array<AutomationRule>
     */
    public function findActiveByOrganizationId(Uuid $organizationId, int $limit = 100): array;

    /**
     * @return array<AutomationRule>
     */
    public function findByOrganizationId(Uuid $organizationId, int $limit = 100): array;

    public function delete(Uuid $id): void;

    public function isPriorityTaken(Uuid $organizationId, int $priority, ?Uuid $excludeId = null): bool;
}
