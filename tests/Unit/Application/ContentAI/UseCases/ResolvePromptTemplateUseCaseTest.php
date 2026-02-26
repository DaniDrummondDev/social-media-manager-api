<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\PromptTemplateResolverInterface;
use App\Application\ContentAI\DTOs\ResolvedPromptOutput;
use App\Application\ContentAI\DTOs\ResolvedPromptResult;
use App\Application\ContentAI\DTOs\ResolvePromptTemplateInput;
use App\Application\ContentAI\UseCases\ResolvePromptTemplateUseCase;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->resolver = Mockery::mock(PromptTemplateResolverInterface::class);

    $this->useCase = new ResolvePromptTemplateUseCase(
        $this->resolver,
    );
});

it('resolves a prompt template via resolver', function () {
    $templateId = (string) Uuid::generate();

    $this->resolver->shouldReceive('resolve')->once()->andReturn(new ResolvedPromptResult(
        templateId: $templateId,
        experimentId: null,
        systemPrompt: 'You are a social media expert.',
        userPromptTemplate: 'Write a title for: {topic}',
        variables: ['topic'],
        variantSelected: null,
    ));

    $output = $this->useCase->execute(new ResolvePromptTemplateInput(
        organizationId: (string) Uuid::generate(),
        generationType: 'title',
    ));

    expect($output)->toBeInstanceOf(ResolvedPromptOutput::class)
        ->and($output->templateId)->toBe($templateId)
        ->and($output->experimentId)->toBeNull()
        ->and($output->systemPrompt)->toBe('You are a social media expert.')
        ->and($output->variables)->toBe(['topic'])
        ->and($output->variantSelected)->toBeNull();
});

it('returns experiment context when A/B test is active', function () {
    $templateId = (string) Uuid::generate();
    $experimentId = (string) Uuid::generate();

    $this->resolver->shouldReceive('resolve')->once()->andReturn(new ResolvedPromptResult(
        templateId: $templateId,
        experimentId: $experimentId,
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        variantSelected: 'A',
    ));

    $output = $this->useCase->execute(new ResolvePromptTemplateInput(
        organizationId: (string) Uuid::generate(),
        generationType: 'title',
    ));

    expect($output->experimentId)->toBe($experimentId)
        ->and($output->variantSelected)->toBe('A');
});
