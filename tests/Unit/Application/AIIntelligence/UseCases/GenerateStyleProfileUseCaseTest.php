<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\StyleProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\GenerateStyleProfileInput;
use App\Application\AIIntelligence\DTOs\StyleAnalysisResult;
use App\Application\AIIntelligence\DTOs\StyleProfileOutput;
use App\Application\AIIntelligence\UseCases\GenerateStyleProfileUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\OrgStyleProfile;
use App\Domain\AIIntelligence\Exceptions\InsufficientEditDataException;
use App\Domain\AIIntelligence\Repositories\OrgStyleProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\StylePreferences;
use App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->feedbackRepository = Mockery::mock(GenerationFeedbackRepositoryInterface::class);
    $this->profileRepository = Mockery::mock(OrgStyleProfileRepositoryInterface::class);
    $this->styleAnalyzer = Mockery::mock(StyleProfileAnalyzerInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new GenerateStyleProfileUseCase(
        $this->feedbackRepository,
        $this->profileRepository,
        $this->styleAnalyzer,
        $this->eventDispatcher,
    );

    $this->analysisResult = new StyleAnalysisResult(
        tonePreferences: ['preferred' => 'casual'],
        lengthPreferences: ['avg_preferred_length' => 280],
        vocabularyPreferences: ['added_words' => ['awesome']],
        structurePreferences: ['uses_emojis' => true],
        hashtagPreferences: ['avg_count' => 5],
        styleSummary: 'Casual tone with emojis',
        sampleSize: 30,
    );
});

it('generates a new style profile when none exists', function () {
    $this->feedbackRepository->shouldReceive('countByOrganizationAndType')
        ->once()->andReturn(['edited' => 15, 'accepted' => 20, 'rejected' => 5]);
    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->once()->andReturn($this->analysisResult);
    $this->profileRepository->shouldReceive('findActiveByOrganizationAndType')
        ->once()->andReturn(null);
    $this->profileRepository->shouldReceive('save')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new GenerateStyleProfileInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        generationType: 'title',
    ));

    expect($output)->toBeInstanceOf(StyleProfileOutput::class)
        ->and($output->sampleSize)->toBe(30)
        ->and($output->styleSummary)->toBe('Casual tone with emojis');
});

it('refreshes existing style profile', function () {
    $existingProfile = OrgStyleProfile::create(
        organizationId: Uuid::generate(),
        generationType: 'title',
        sampleSize: 10,
        stylePreferences: StylePreferences::fromArray([]),
        styleSummary: 'Old summary',
        userId: 'user-1',
    );

    $this->feedbackRepository->shouldReceive('countByOrganizationAndType')
        ->once()->andReturn(['edited' => 20, 'accepted' => 30, 'rejected' => 5]);
    $this->styleAnalyzer->shouldReceive('analyzeEditPatterns')
        ->once()->andReturn($this->analysisResult);
    $this->profileRepository->shouldReceive('findActiveByOrganizationAndType')
        ->once()->andReturn($existingProfile);
    $this->profileRepository->shouldReceive('save')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new GenerateStyleProfileInput(
        organizationId: (string) $existingProfile->organizationId,
        userId: 'user-1',
        generationType: 'title',
    ));

    expect($output->sampleSize)->toBe(30)
        ->and($output->id)->toBe((string) $existingProfile->id);
});

it('throws InsufficientEditDataException when not enough edits', function () {
    $this->feedbackRepository->shouldReceive('countByOrganizationAndType')
        ->once()->andReturn(['edited' => 5, 'accepted' => 20, 'rejected' => 3]);

    $this->useCase->execute(new GenerateStyleProfileInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        generationType: 'title',
    ));
})->throws(InsufficientEditDataException::class);
