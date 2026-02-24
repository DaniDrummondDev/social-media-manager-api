<?php

declare(strict_types=1);

use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\Events\ContentGenerated;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;

function createAIGeneration(): AIGeneration
{
    return AIGeneration::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        type: GenerationType::Title,
        input: ['topic' => 'Test topic'],
        output: ['suggestions' => ['Title 1', 'Title 2']],
        usage: new AIUsage(
            tokensInput: 120,
            tokensOutput: 85,
            model: 'gpt-4o',
            costEstimate: 0.003,
            durationMs: 1200,
        ),
    );
}

it('creates generation with event', function () {
    $generation = createAIGeneration();

    expect($generation->type)->toBe(GenerationType::Title)
        ->and($generation->input)->toBe(['topic' => 'Test topic'])
        ->and($generation->output)->toBe(['suggestions' => ['Title 1', 'Title 2']])
        ->and($generation->usage->tokensInput)->toBe(120)
        ->and($generation->usage->model)->toBe('gpt-4o')
        ->and($generation->domainEvents)->toHaveCount(1)
        ->and($generation->domainEvents[0])->toBeInstanceOf(ContentGenerated::class);
});

it('reconstitutes without events', function () {
    $generation = AIGeneration::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        type: GenerationType::Description,
        input: [],
        output: [],
        usage: new AIUsage(100, 50, 'gpt-4o-mini', 0.001, 500),
        createdAt: new DateTimeImmutable,
    );

    expect($generation->domainEvents)->toBeEmpty()
        ->and($generation->type)->toBe(GenerationType::Description);
});

it('releases events', function () {
    $generation = createAIGeneration();
    expect($generation->domainEvents)->toHaveCount(1);

    $released = $generation->releaseEvents();
    expect($released->domainEvents)->toBeEmpty();
});

it('preserves all data after release events', function () {
    $generation = createAIGeneration();
    $released = $generation->releaseEvents();

    expect($released->id)->toBe($generation->id)
        ->and($released->type)->toBe($generation->type)
        ->and($released->input)->toBe($generation->input)
        ->and($released->output)->toBe($generation->output)
        ->and($released->usage->tokensInput)->toBe($generation->usage->tokensInput);
});
