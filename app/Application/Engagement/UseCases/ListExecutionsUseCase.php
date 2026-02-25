<?php

declare(strict_types=1);

namespace App\Application\Engagement\UseCases;

use App\Application\Engagement\DTOs\AutomationExecutionOutput;
use App\Application\Engagement\DTOs\ListExecutionsInput;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListExecutionsUseCase
{
    public function __construct(
        private readonly AutomationExecutionRepositoryInterface $executionRepository,
    ) {}

    /**
     * @return array<AutomationExecutionOutput>
     */
    public function execute(ListExecutionsInput $input): array
    {
        $ruleId = Uuid::fromString($input->ruleId);

        $executions = $this->executionRepository->findByRuleId(
            $ruleId,
            $input->cursor,
            $input->limit,
        );

        return array_map(
            fn ($execution) => AutomationExecutionOutput::fromEntity($execution),
            $executions,
        );
    }
}
