<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\GenerateStyleProfileInput;
use App\Application\AIIntelligence\DTOs\StyleProfileOutput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\OrgStyleProfile;
use App\Domain\AIIntelligence\Exceptions\InsufficientEditDataException;
use App\Domain\AIIntelligence\Repositories\OrgStyleProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\StylePreferences;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GenerateStyleProfileUseCase
{
    public function __construct(
        private readonly GenerationFeedbackRepositoryInterface $feedbackRepository,
        private readonly OrgStyleProfileRepositoryInterface $profileRepository,
        private readonly StyleProfileAnalyzerInterface $styleAnalyzer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GenerateStyleProfileInput $input): StyleProfileOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        // RN-ALL-39: minimum 10 edited feedbacks required
        $counts = $this->feedbackRepository->countByOrganizationAndType(
            organizationId: $organizationId,
            generationType: $input->generationType,
        );
        $editCount = $counts['edited'];

        if (! OrgStyleProfile::hasEnoughData($editCount)) {
            throw new InsufficientEditDataException(
                required: OrgStyleProfile::minEditsRequired(),
                actual: $editCount,
            );
        }

        // Analyze edit patterns via LLM
        $analysis = $this->styleAnalyzer->analyzeEditPatterns(
            organizationId: $input->organizationId,
            generationType: $input->generationType,
        );

        $stylePreferences = StylePreferences::fromArray([
            'tone_preferences' => $analysis->tonePreferences,
            'length_preferences' => $analysis->lengthPreferences,
            'vocabulary_preferences' => $analysis->vocabularyPreferences,
            'structure_preferences' => $analysis->structurePreferences,
            'hashtag_preferences' => $analysis->hashtagPreferences,
        ]);

        // Check for existing profile to refresh
        $existing = $this->profileRepository->findActiveByOrganizationAndType(
            organizationId: $organizationId,
            generationType: $input->generationType,
        );

        if ($existing !== null) {
            $profile = $existing->refresh(
                sampleSize: $analysis->sampleSize,
                stylePreferences: $stylePreferences,
                styleSummary: $analysis->styleSummary,
                userId: $input->userId,
            );
        } else {
            $profile = OrgStyleProfile::create(
                organizationId: $organizationId,
                generationType: $input->generationType,
                sampleSize: $analysis->sampleSize,
                stylePreferences: $stylePreferences,
                styleSummary: $analysis->styleSummary,
                userId: $input->userId,
            );
        }

        $this->profileRepository->save($profile);
        $this->eventDispatcher->dispatch(...$profile->domainEvents);

        return StyleProfileOutput::fromEntity($profile);
    }
}
