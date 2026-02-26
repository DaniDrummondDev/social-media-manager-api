<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GetSafetyChecksInput;
use App\Application\AIIntelligence\DTOs\SafetyCheckOutput;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetSafetyChecksUseCase
{
    public function __construct(
        private readonly BrandSafetyCheckRepositoryInterface $checkRepository,
    ) {}

    /**
     * @return array<SafetyCheckOutput>
     */
    public function execute(GetSafetyChecksInput $input): array
    {
        $contentId = Uuid::fromString($input->contentId);

        $checks = $this->checkRepository->findByContentId($contentId);

        $filtered = array_filter(
            $checks,
            fn ($check) => (string) $check->organizationId === $input->organizationId,
        );

        return array_map(
            fn ($check) => SafetyCheckOutput::fromEntity($check),
            array_values($filtered),
        );
    }
}
