<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\AdaptContentInput;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Application\ContentAI\UseCases\AdaptContentUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->textGenerator = Mockery::mock(TextGeneratorInterface::class);
    $this->generationRepository = Mockery::mock(AIGenerationRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new AdaptContentUseCase(
        $this->textGenerator,
        $this->generationRepository,
        $this->eventDispatcher,
    );
});

it('adapts content and returns AIGenerationOutput with CrossNetworkAdaptation type', function () {
    $result = new TextGenerationResult(
        output: [
            'adaptations' => [
                'tiktok' => ['title' => 'TikTok Title', 'description' => 'TikTok desc'],
                'youtube' => ['title' => 'YouTube Title', 'description' => 'YouTube desc'],
            ],
        ],
        tokensInput: 200,
        tokensOutput: 350,
        model: 'gpt-4o',
        durationMs: 2500,
        costEstimate: 0.008,
    );

    $this->textGenerator->shouldReceive('adaptContent')->once()->andReturn($result);
    $this->generationRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new AdaptContentInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
        sourceNetwork: 'instagram',
        targetNetworks: ['tiktok', 'youtube'],
        preserveTone: true,
    ));

    expect($output->type)->toBe('cross_network_adaptation')
        ->and($output->tokensInput)->toBe(200)
        ->and($output->tokensOutput)->toBe(350)
        ->and($output->model)->toBe('gpt-4o');
});
