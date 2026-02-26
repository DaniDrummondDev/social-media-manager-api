<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\RunSafetyCheckInput;
use App\Application\AIIntelligence\DTOs\RunSafetyCheckOutput;
use App\Domain\AIIntelligence\Entities\BrandSafetyCheck;
use App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class RunSafetyCheckUseCase
{
    public function __construct(
        private readonly BrandSafetyCheckRepositoryInterface $checkRepository,
    ) {}

    public function execute(RunSafetyCheckInput $input): RunSafetyCheckOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $contentId = Uuid::fromString($input->contentId);

        $check = BrandSafetyCheck::create(
            organizationId: $organizationId,
            contentId: $contentId,
            provider: null,
        );

        $this->checkRepository->create($check);

        return new RunSafetyCheckOutput(
            checkId: (string) $check->id,
            contentId: (string) $check->contentId,
            status: $check->overallStatus->value,
            message: 'Safety check queued. Results will be available shortly.',
        );
    }
}
