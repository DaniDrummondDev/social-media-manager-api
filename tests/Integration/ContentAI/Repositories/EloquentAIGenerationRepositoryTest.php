<?php

declare(strict_types=1);

use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(AIGenerationRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'ai-gen-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'ai-gen-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

function createTestGeneration(string $orgId, string $userId, ?GenerationType $type = null): AIGeneration
{
    return AIGeneration::create(
        organizationId: Uuid::fromString($orgId),
        userId: Uuid::fromString($userId),
        type: $type ?? GenerationType::Title,
        input: ['topic' => 'Test topic'],
        output: ['suggestions' => ['Title 1']],
        usage: new AIUsage(120, 85, 'gpt-4o', 0.003, 1200),
    );
}

it('creates and retrieves by id', function () {
    $generation = createTestGeneration($this->orgId, $this->userId);
    $this->repository->create($generation);

    $found = $this->repository->findById($generation->id);

    expect($found)->not->toBeNull()
        ->and($found->type)->toBe(GenerationType::Title)
        ->and($found->input)->toBe(['topic' => 'Test topic'])
        ->and($found->usage->tokensInput)->toBe(120)
        ->and($found->usage->model)->toBe('gpt-4o');
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds by organization id', function () {
    $g1 = createTestGeneration($this->orgId, $this->userId, GenerationType::Title);
    $g2 = createTestGeneration($this->orgId, $this->userId, GenerationType::Description);

    $this->repository->create($g1);
    $this->repository->create($g2);

    $all = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId));
    expect($all)->toHaveCount(2);

    $filtered = $this->repository->findByOrganizationId(Uuid::fromString($this->orgId), 'title');
    expect($filtered)->toHaveCount(1);
});

it('counts by organization and month', function () {
    $g1 = createTestGeneration($this->orgId, $this->userId);
    $g2 = createTestGeneration($this->orgId, $this->userId);

    $this->repository->create($g1);
    $this->repository->create($g2);

    $now = new DateTimeImmutable;
    $count = $this->repository->countByOrganizationAndMonth(
        Uuid::fromString($this->orgId),
        (int) $now->format('Y'),
        (int) $now->format('m'),
    );

    expect($count)->toBe(2);
});

it('sums usage by organization and month', function () {
    $g1 = createTestGeneration($this->orgId, $this->userId);
    $g2 = createTestGeneration($this->orgId, $this->userId);

    $this->repository->create($g1);
    $this->repository->create($g2);

    $now = new DateTimeImmutable;
    $usage = $this->repository->sumUsageByOrganizationAndMonth(
        Uuid::fromString($this->orgId),
        (int) $now->format('Y'),
        (int) $now->format('m'),
    );

    expect($usage['tokens_input'])->toBe(240)
        ->and($usage['tokens_output'])->toBe(170);
});
