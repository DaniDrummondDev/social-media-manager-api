<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\AutomationRule;
use App\Domain\Engagement\ValueObjects\ActionType;
use App\Domain\Engagement\ValueObjects\ConditionOperator;
use App\Domain\Engagement\ValueObjects\RuleCondition;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Repositories\EloquentAutomationRuleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->userId = (string) Str::uuid();
    $this->orgId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test',
        'email' => 'test-'.Str::random(6).'@example.com',
        'password' => 'hashed',
        'timezone' => 'UTC',
        'email_verified_at' => now()->toDateTimeString(),
        'two_factor_enabled' => false,
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and finds by id', function () {
    $repo = app(EloquentAutomationRuleRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $rule = AutomationRule::create(
        organizationId: $orgId,
        name: 'Test Rule',
        priority: 1,
        conditions: [new RuleCondition('keyword', ConditionOperator::Contains, 'hello')],
        actionType: ActionType::ReplyFixed,
        responseTemplate: 'Thanks!',
    );

    $repo->create($rule);

    $found = $repo->findById($rule->id);

    expect($found)->not->toBeNull()
        ->and($found->name)->toBe('Test Rule')
        ->and($found->priority)->toBe(1)
        ->and($found->conditions)->toHaveCount(1)
        ->and($found->conditions[0]->field)->toBe('keyword')
        ->and($found->conditions[0]->operator)->toBe(ConditionOperator::Contains);
});

it('finds active by organization ordered by priority', function () {
    $repo = app(EloquentAutomationRuleRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $rule1 = AutomationRule::create(
        organizationId: $orgId,
        name: 'Rule Priority 2',
        priority: 2,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $rule2 = AutomationRule::create(
        organizationId: $orgId,
        name: 'Rule Priority 1',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $inactive = AutomationRule::create(
        organizationId: $orgId,
        name: 'Inactive',
        priority: 3,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    )->deactivate();

    $repo->create($rule1);
    $repo->create($rule2);
    $repo->create($inactive);
    $repo->update($inactive);

    $active = $repo->findActiveByOrganizationId($orgId);

    expect($active)->toHaveCount(2)
        ->and($active[0]->name)->toBe('Rule Priority 1')
        ->and($active[1]->name)->toBe('Rule Priority 2');
});

it('checks priority taken', function () {
    $repo = app(EloquentAutomationRuleRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $rule = AutomationRule::create(
        organizationId: $orgId,
        name: 'Existing',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $repo->create($rule);

    expect($repo->isPriorityTaken($orgId, 1))->toBeTrue()
        ->and($repo->isPriorityTaken($orgId, 2))->toBeFalse()
        ->and($repo->isPriorityTaken($orgId, 1, $rule->id))->toBeFalse();
});

it('excludes soft-deleted from organization list', function () {
    $repo = app(EloquentAutomationRuleRepository::class);
    $orgId = Uuid::fromString($this->orgId);

    $rule = AutomationRule::create(
        organizationId: $orgId,
        name: 'To Delete',
        priority: 1,
        conditions: [],
        actionType: ActionType::ReplyFixed,
    );

    $repo->create($rule);

    $deleted = $rule->softDelete();
    $repo->update($deleted);

    $rules = $repo->findByOrganizationId($orgId);

    expect($rules)->toHaveCount(0);
});
