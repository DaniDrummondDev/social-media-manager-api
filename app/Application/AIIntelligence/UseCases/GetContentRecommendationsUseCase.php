<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\EmbeddingGeneratorInterface;
use App\Application\AIIntelligence\Contracts\SimilaritySearchInterface;
use App\Application\AIIntelligence\DTOs\ContentRecommendationsOutput;
use App\Application\AIIntelligence\DTOs\GetContentRecommendationsInput;
use App\Application\AIIntelligence\Exceptions\ContentProfileNotFoundException;
use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetContentRecommendationsUseCase
{
    public function __construct(
        private readonly ContentProfileRepositoryInterface $profileRepository,
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
        private readonly SimilaritySearchInterface $similaritySearch,
    ) {}

    public function execute(GetContentRecommendationsInput $input): ContentRecommendationsOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $profile = $this->profileRepository->findByOrganization(
            organizationId: $organizationId,
            provider: $input->provider,
        );

        if ($profile === null || $profile->centroidEmbedding === null) {
            throw new ContentProfileNotFoundException;
        }

        $topicEmbedding = $this->embeddingGenerator->generate($input->topic);

        $similar = $this->similaritySearch->findSimilar(
            embedding: $topicEmbedding,
            organizationId: $input->organizationId,
            provider: $input->provider,
            limit: $input->limit,
        );

        $recommendations = [];

        foreach ($similar as $item) {
            $bestFormat = $profile->engagementPatterns?->bestContentTypes[0] ?? 'post';

            $recommendations[] = [
                'topic' => $input->topic,
                'similarity_score' => round($item['similarity'], 2),
                'reasoning' => $this->buildReasoning($profile, $item['similarity']),
                'suggested_format' => $bestFormat,
                'reference_content_ids' => [$item['content_id']],
            ];
        }

        return new ContentRecommendationsOutput(recommendations: $recommendations);
    }

    private function buildReasoning(ContentProfile $profile, float $similarity): string
    {
        if ($similarity >= 0.8) {
            return 'Highly aligned with your top-performing content DNA.';
        }

        if ($similarity >= 0.6) {
            return 'Moderately aligned with your content patterns.';
        }

        return 'Exploratory topic — may diversify your content mix.';
    }
}
