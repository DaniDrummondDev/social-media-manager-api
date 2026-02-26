<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\PredictPerformanceInput;
use App\Application\AIIntelligence\DTOs\PredictionOutput;
use App\Application\AIIntelligence\Exceptions\InsufficientDataException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\ValueObjects\Uuid;

final class PredictPerformanceUseCase
{
    public function __construct(
        private readonly PerformancePredictionRepositoryInterface $predictionRepository,
        private readonly ContentProfileRepositoryInterface $profileRepository,
        private readonly SimilaritySearchInterface $similaritySearch,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return array<PredictionOutput>
     */
    public function execute(PredictPerformanceInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $contentId = Uuid::fromString($input->contentId);

        $predictions = [];

        foreach ($input->providers as $provider) {
            $profile = $this->profileRepository->findByOrganization(
                organizationId: $organizationId,
                provider: $provider,
            );

            if ($profile === null) {
                throw new InsufficientDataException(
                    "No content profile available for provider: {$provider}",
                );
            }

            $similarContentIds = null;
            $contentSimilarity = 50;

            if ($profile->centroidEmbedding !== null) {
                $similar = $this->similaritySearch->findSimilar(
                    embedding: $profile->centroidEmbedding,
                    organizationId: $input->organizationId,
                    provider: $provider,
                    limit: 5,
                );

                $similarContentIds = array_map(fn (array $s) => $s['content_id'], $similar);
                $contentSimilarity = $similar !== []
                    ? (int) round($similar[0]['similarity'] * 100)
                    : 50;
            }

            $breakdown = PredictionBreakdown::create(
                contentSimilarity: $contentSimilarity,
                timing: 50,
                hashtags: 50,
                length: 50,
                mediaType: 50,
            );

            $overallScore = PredictionScore::create(
                (int) round(($breakdown->contentSimilarity + $breakdown->timing
                    + $breakdown->hashtags + $breakdown->length + $breakdown->mediaType) / 5),
            );

            $prediction = PerformancePrediction::create(
                organizationId: $organizationId,
                contentId: $contentId,
                provider: $provider,
                overallScore: $overallScore,
                breakdown: $breakdown,
                similarContentIds: $similarContentIds,
                recommendations: [],
                modelVersion: 'v1',
                userId: $input->userId,
            );

            $this->predictionRepository->create($prediction);
            $this->eventDispatcher->dispatch(...$prediction->domainEvents);

            $predictions[] = PredictionOutput::fromEntity($prediction);
        }

        return $predictions;
    }
}
