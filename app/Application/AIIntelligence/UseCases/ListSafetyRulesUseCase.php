<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\ListSafetyRulesInput;
use App\Application\AIIntelligence\DTOs\SafetyRuleOutput;
use App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListSafetyRulesUseCase
{
    public function __construct(
        private readonly BrandSafetyRuleRepositoryInterface $ruleRepository,
    ) {}

    /**
     * @return array{items: array<SafetyRuleOutput>, next_cursor: ?string}
     */
    public function execute(ListSafetyRulesInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $result = $this->ruleRepository->findByOrganizationId(
            organizationId: $organizationId,
            cursor: $input->cursor,
            limit: $input->limit,
        );

        $items = array_map(
            fn ($rule) => SafetyRuleOutput::fromEntity($rule),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
        ];
    }
}
