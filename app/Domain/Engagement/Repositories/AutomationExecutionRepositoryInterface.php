<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\AutomationExecution;
use App\Domain\Shared\ValueObjects\Uuid;

interface AutomationExecutionRepositoryInterface
{
    public function create(AutomationExecution $execution): void;

    /**
     * @return array<AutomationExecution>
     */
    public function findByRuleId(Uuid $ruleId, ?string $cursor = null, int $limit = 20): array;

    public function countTodayByRule(Uuid $ruleId): int;
}
