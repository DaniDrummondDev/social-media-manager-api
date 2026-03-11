<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\GenerateTitleInput;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Application\ContentAI\Services\BriefContextResolver;
use App\Application\ContentAI\UseCases\GenerateTitleUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->textGenerator = Mockery::mock(TextGeneratorInterface::class);
    $this->generationRepository = Mockery::mock(AIGenerationRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $campaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
    $this->briefContextResolver = new BriefContextResolver($campaignRepo);

    $this->useCase = new GenerateTitleUseCase(
        $this->textGenerator,
        $this->generationRepository,
        $this->eventDispatcher,
        $this->briefContextResolver,
    );
});

it('generates title successfully', function () {
    $result = new TextGenerationResult(
        output: ['suggestions' => [['title' => 'Generated Title', 'character_count' => 15, 'tone' => 'professional']]],
        tokensInput: 120,
        tokensOutput: 85,
        model: 'gpt-4o',
        durationMs: 1200,
        costEstimate: 0.003,
    );

    $this->textGenerator->shouldReceive('generateTitle')->once()->andReturn($result);
    $this->generationRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new GenerateTitleInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        topic: 'Test topic for title generation',
        socialNetwork: 'instagram',
        tone: 'professional',
        language: 'pt_BR',
    ));

    expect($output->type)->toBe('title')
        ->and($output->tokensInput)->toBe(120)
        ->and($output->model)->toBe('gpt-4o');
});
