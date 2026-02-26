<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GenerateGapAnalysisInput;
use App\Application\AIIntelligence\DTOs\GenerateGapAnalysisOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GenerateGapAnalysisUseCase
{
    public function __construct(
        private readonly ContentGapAnalysisRepositoryInterface $gapAnalysisRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GenerateGapAnalysisInput $input): GenerateGapAnalysisOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $now = new DateTimeImmutable;

        $analysis = ContentGapAnalysis::create(
            organizationId: $organizationId,
            competitorQueryIds: $input->competitorQueryIds,
            analysisPeriodStart: $now->modify("-{$input->periodDays} days"),
            analysisPeriodEnd: $now,
            userId: $input->userId,
        );

        $this->gapAnalysisRepository->create($analysis);
        $this->eventDispatcher->dispatch(...$analysis->domainEvents);

        return new GenerateGapAnalysisOutput(
            analysisId: (string) $analysis->id,
            status: $analysis->status->value,
            message: 'Gap analysis is being generated.',
        );
    }
}
