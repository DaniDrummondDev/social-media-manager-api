<?php

declare(strict_types=1);

use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\ContentAI\Events\PromptPerformanceCalculated;
use App\Domain\ContentAI\Events\PromptTemplateCreated;
use App\Domain\ContentAI\ValueObjects\PerformanceScore;
use App\Domain\Shared\ValueObjects\Uuid;

function createTemplate(array $overrides = []): PromptTemplate
{
    return PromptTemplate::create(
        organizationId: array_key_exists('organizationId', $overrides) ? $overrides['organizationId'] : Uuid::generate(),
        generationType: $overrides['generationType'] ?? 'title',
        version: $overrides['version'] ?? 'v1',
        name: $overrides['name'] ?? 'Default Title Template',
        systemPrompt: $overrides['systemPrompt'] ?? 'You are a social media expert.',
        userPromptTemplate: $overrides['userPromptTemplate'] ?? 'Write a title for: {topic}',
        variables: $overrides['variables'] ?? ['topic'],
        isDefault: $overrides['isDefault'] ?? false,
        createdBy: $overrides['createdBy'] ?? Uuid::generate(),
    );
}

it('creates with PromptTemplateCreated event', function () {
    $template = createTemplate();

    expect($template->isActive)->toBeTrue()
        ->and($template->isDefault)->toBeFalse()
        ->and($template->performanceScore)->toBeNull()
        ->and($template->totalUses)->toBe(0)
        ->and($template->totalAccepted)->toBe(0)
        ->and($template->totalEdited)->toBe(0)
        ->and($template->totalRejected)->toBe(0)
        ->and($template->variables)->toBe(['topic'])
        ->and($template->domainEvents)->toHaveCount(1)
        ->and($template->domainEvents[0])->toBeInstanceOf(PromptTemplateCreated::class);
});

it('creates system template with null organizationId', function () {
    $template = createTemplate(['organizationId' => null]);

    expect($template->organizationId)->toBeNull()
        ->and($template->isSystemTemplate())->toBeTrue();
});

it('identifies non-system template', function () {
    $template = createTemplate();

    expect($template->isSystemTemplate())->toBeFalse();
});

it('records accepted usage', function () {
    $template = createTemplate();
    $updated = $template->recordUsage('accepted');

    expect($updated->totalUses)->toBe(1)
        ->and($updated->totalAccepted)->toBe(1)
        ->and($updated->totalEdited)->toBe(0)
        ->and($updated->totalRejected)->toBe(0);
});

it('records edited usage', function () {
    $template = createTemplate();
    $updated = $template->recordUsage('edited');

    expect($updated->totalUses)->toBe(1)
        ->and($updated->totalAccepted)->toBe(0)
        ->and($updated->totalEdited)->toBe(1)
        ->and($updated->totalRejected)->toBe(0);
});

it('records rejected usage', function () {
    $template = createTemplate();
    $updated = $template->recordUsage('rejected');

    expect($updated->totalUses)->toBe(1)
        ->and($updated->totalAccepted)->toBe(0)
        ->and($updated->totalEdited)->toBe(0)
        ->and($updated->totalRejected)->toBe(1);
});

it('recalculates performance with event', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $template = PromptTemplate::reconstitute(
        id: $id,
        organizationId: $orgId,
        generationType: 'title',
        version: 'v1',
        name: 'Test',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        isActive: true,
        isDefault: false,
        performanceScore: null,
        totalUses: 10,
        totalAccepted: 8,
        totalEdited: 2,
        totalRejected: 0,
        createdBy: Uuid::generate(),
        createdAt: $now,
        updatedAt: $now,
    );

    $recalculated = $template->recalculatePerformance('system');

    // Formula: (8 + 2 × 0.7) / 10 × 100 = 94.0
    expect($recalculated->performanceScore)->not->toBeNull()
        ->and($recalculated->performanceScore->value)->toBe(94.0)
        ->and($recalculated->domainEvents)->toHaveCount(1)
        ->and($recalculated->domainEvents[0])->toBeInstanceOf(PromptPerformanceCalculated::class)
        ->and($recalculated->domainEvents[0]->performanceScore)->toBe(94.0);
});

it('deactivates a template', function () {
    $template = createTemplate(['isDefault' => true]);
    $deactivated = $template->deactivate();

    expect($deactivated->isActive)->toBeFalse()
        ->and($deactivated->isDefault)->toBeFalse()
        ->and($template->isActive)->toBeTrue(); // original unchanged
});

it('isEligibleForAutoSelection requires 20+ uses and positive score', function () {
    $now = new DateTimeImmutable;

    $eligible = PromptTemplate::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Test',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        isActive: true,
        isDefault: false,
        performanceScore: PerformanceScore::fromFloat(80.0),
        totalUses: 20,
        totalAccepted: 16,
        totalEdited: 4,
        totalRejected: 0,
        createdBy: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($eligible->isEligibleForAutoSelection())->toBeTrue();
});

it('is not eligible with fewer than 20 uses', function () {
    $now = new DateTimeImmutable;

    $notEnoughUses = PromptTemplate::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Test',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: [],
        isActive: true,
        isDefault: false,
        performanceScore: PerformanceScore::fromFloat(80.0),
        totalUses: 19,
        totalAccepted: 15,
        totalEdited: 4,
        totalRejected: 0,
        createdBy: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($notEnoughUses->isEligibleForAutoSelection())->toBeFalse();
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $template = PromptTemplate::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        generationType: 'title',
        version: 'v1',
        name: 'Test',
        systemPrompt: 'sys',
        userPromptTemplate: 'usr',
        variables: ['topic'],
        isActive: true,
        isDefault: true,
        performanceScore: null,
        totalUses: 0,
        totalAccepted: 0,
        totalEdited: 0,
        totalRejected: 0,
        createdBy: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($template->id)->toEqual($id)
        ->and($template->domainEvents)->toBeEmpty()
        ->and($template->isDefault)->toBeTrue();
});
