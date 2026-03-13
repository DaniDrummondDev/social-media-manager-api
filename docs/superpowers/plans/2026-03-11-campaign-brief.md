# Campaign Brief Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users to define a creative brief per campaign that provides base context for AI content generation, with 3 generation modes (fields_only, brief_only, brief_and_fields).

**Architecture:** New `CampaignBrief` Value Object in Campaign domain, 4 nullable columns added to campaigns table, brief context injected into AI generation pipeline via topic prepend in UseCases. No changes to `TextGeneratorInterface`.

**Tech Stack:** PHP 8.4, Laravel 12, PostgreSQL, Pest 4, DDD/Clean Architecture

**Spec:** `docs/superpowers/specs/2026-03-11-campaign-brief-design.md`

---

## Chunk 1: Domain Layer (Value Object + Entity + Exceptions)

### File Structure

| Action | File | Responsibility |
|--------|------|---------------|
| Create | `app/Domain/Campaign/ValueObjects/CampaignBrief.php` | Immutable VO encapsulating brief fields |
| Create | `app/Domain/Campaign/Exceptions/CampaignBriefRequiredException.php` | Exception when brief_only/brief_and_fields used without brief |
| Modify | `app/Domain/Campaign/Entities/Campaign.php` | Add `?CampaignBrief $brief` to all factory methods |
| Create | `tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php` | VO unit tests |
| Create | `tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php` | Entity tests with brief |

---

### Task 1: CampaignBrief Value Object

**Files:**
- Create: `app/Domain/Campaign/ValueObjects/CampaignBrief.php`
- Create: `tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php

declare(strict_types=1);

use App\Domain\Campaign\ValueObjects\CampaignBrief;

it('isEmpty returns true when all fields are null', function () {
    $brief = new CampaignBrief(
        text: null,
        targetAudience: null,
        restrictions: null,
        cta: null,
    );

    expect($brief->isEmpty())->toBeTrue();
});

it('isEmpty returns false when at least one field is set', function () {
    $brief = new CampaignBrief(
        text: 'Campaign about Black Friday',
        targetAudience: null,
        restrictions: null,
        cta: null,
    );

    expect($brief->isEmpty())->toBeFalse();
});

it('toPromptContext includes all non-null fields', function () {
    $brief = new CampaignBrief(
        text: 'Black Friday campaign for fashion store',
        targetAudience: 'Young adults 18-30',
        restrictions: 'No aggressive language',
        cta: 'Shop now with 50% off',
    );

    $context = $brief->toPromptContext();

    expect($context)
        ->toContain('Objective: Black Friday campaign for fashion store')
        ->toContain('Target Audience: Young adults 18-30')
        ->toContain('Restrictions: No aggressive language')
        ->toContain('Desired CTA: Shop now with 50% off')
        ->toContain('[CAMPAIGN BRIEF]');
});

it('toPromptContext omits null fields', function () {
    $brief = new CampaignBrief(
        text: 'Simple campaign brief',
        targetAudience: null,
        restrictions: null,
        cta: null,
    );

    $context = $brief->toPromptContext();

    expect($context)
        ->toContain('Objective: Simple campaign brief')
        ->not->toContain('Target Audience')
        ->not->toContain('Restrictions')
        ->not->toContain('Desired CTA');
});

it('mergeWith returns self when override is null', function () {
    $brief = new CampaignBrief(
        text: 'Original',
        targetAudience: 'Teens',
        restrictions: null,
        cta: null,
    );

    $merged = $brief->mergeWith(null);

    expect($merged)->toBe($brief);
});

it('mergeWith preserves existing fields when override fields are null', function () {
    $existing = new CampaignBrief(
        text: 'Original text',
        targetAudience: 'Teens',
        restrictions: 'No violence',
        cta: 'Buy now',
    );

    $override = new CampaignBrief(
        text: null,
        targetAudience: null,
        restrictions: null,
        cta: 'Updated CTA',
    );

    $merged = $existing->mergeWith($override);

    expect($merged->text)->toBe('Original text')
        ->and($merged->targetAudience)->toBe('Teens')
        ->and($merged->restrictions)->toBe('No violence')
        ->and($merged->cta)->toBe('Updated CTA');
});

it('mergeWith overrides all fields when all provided', function () {
    $existing = new CampaignBrief(
        text: 'Old',
        targetAudience: 'Old audience',
        restrictions: 'Old restrictions',
        cta: 'Old CTA',
    );

    $override = new CampaignBrief(
        text: 'New',
        targetAudience: 'New audience',
        restrictions: 'New restrictions',
        cta: 'New CTA',
    );

    $merged = $existing->mergeWith($override);

    expect($merged->text)->toBe('New')
        ->and($merged->targetAudience)->toBe('New audience')
        ->and($merged->restrictions)->toBe('New restrictions')
        ->and($merged->cta)->toBe('New CTA');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php`
Expected: FAIL — class CampaignBrief not found

- [ ] **Step 3: Write CampaignBrief Value Object**

```php
<?php
// app/Domain/Campaign/ValueObjects/CampaignBrief.php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

final readonly class CampaignBrief
{
    public function __construct(
        public ?string $text,
        public ?string $targetAudience,
        public ?string $restrictions,
        public ?string $cta,
    ) {}

    public function isEmpty(): bool
    {
        return $this->text === null
            && $this->targetAudience === null
            && $this->restrictions === null
            && $this->cta === null;
    }

    public function toPromptContext(): string
    {
        $lines = ['[CAMPAIGN BRIEF]'];

        if ($this->text !== null) {
            $lines[] = "Objective: {$this->text}";
        }

        if ($this->targetAudience !== null) {
            $lines[] = "Target Audience: {$this->targetAudience}";
        }

        if ($this->restrictions !== null) {
            $lines[] = "Restrictions: {$this->restrictions}";
        }

        if ($this->cta !== null) {
            $lines[] = "Desired CTA: {$this->cta}";
        }

        $lines[] = '';
        $lines[] = 'Generate content based on this campaign brief context.';

        return implode("\n", $lines);
    }

    public function mergeWith(?self $override): self
    {
        if ($override === null) {
            return $this;
        }

        return new self(
            text: $override->text ?? $this->text,
            targetAudience: $override->targetAudience ?? $this->targetAudience,
            restrictions: $override->restrictions ?? $this->restrictions,
            cta: $override->cta ?? $this->cta,
        );
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php`
Expected: 7 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Domain/Campaign/ValueObjects/CampaignBrief.php tests/Unit/Domain/Campaign/ValueObjects/CampaignBriefTest.php
git commit -m "feat(campaign): add CampaignBrief value object with merge support"
```

---

### Task 2: CampaignBriefRequiredException

**Files:**
- Create: `app/Domain/Campaign/Exceptions/CampaignBriefRequiredException.php`

- [ ] **Step 1: Create the exception**

```php
<?php
// app/Domain/Campaign/Exceptions/CampaignBriefRequiredException.php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CampaignBriefRequiredException extends DomainException
{
    public function __construct(string $campaignId)
    {
        parent::__construct(
            message: "Campaign has no brief defined: {$campaignId}",
            errorCode: 'CAMPAIGN_BRIEF_REQUIRED',
        );
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Domain/Campaign/Exceptions/CampaignBriefRequiredException.php
git commit -m "feat(campaign): add CampaignBriefRequiredException"
```

---

### Task 3: Add brief to Campaign entity

**Files:**
- Modify: `app/Domain/Campaign/Entities/Campaign.php`
- Create: `tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php

declare(strict_types=1);

use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

it('creates campaign without brief', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
    );

    expect($campaign->brief)->toBeNull();
});

it('creates campaign with brief', function () {
    $brief = new CampaignBrief(
        text: 'Black Friday campaign',
        targetAudience: 'Young adults',
        restrictions: null,
        cta: 'Buy now',
    );

    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
        brief: $brief,
    );

    expect($campaign->brief)->not->toBeNull()
        ->and($campaign->brief->text)->toBe('Black Friday campaign')
        ->and($campaign->brief->targetAudience)->toBe('Young adults')
        ->and($campaign->brief->cta)->toBe('Buy now');
});

it('update preserves existing brief when not provided', function () {
    $brief = new CampaignBrief(
        text: 'Original brief',
        targetAudience: null,
        restrictions: null,
        cta: null,
    );

    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
        brief: $brief,
    );

    $updated = $campaign->update(name: 'Updated Name');

    expect($updated->name)->toBe('Updated Name')
        ->and($updated->brief)->not->toBeNull()
        ->and($updated->brief->text)->toBe('Original brief');
});

it('update replaces brief when provided', function () {
    $campaign = Campaign::create(
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
        brief: new CampaignBrief('Old', null, null, null),
    );

    $newBrief = new CampaignBrief('New brief', 'Teens', null, null);
    $updated = $campaign->update(brief: $newBrief);

    expect($updated->brief->text)->toBe('New brief')
        ->and($updated->brief->targetAudience)->toBe('Teens');
});

it('reconstitute includes brief', function () {
    $brief = new CampaignBrief('Reconstituted brief', null, null, null);
    $now = new DateTimeImmutable;

    $campaign = Campaign::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        createdBy: Uuid::generate(),
        name: 'Test',
        description: null,
        startsAt: null,
        endsAt: null,
        status: CampaignStatus::Draft,
        tags: [],
        createdAt: $now,
        updatedAt: $now,
        deletedAt: null,
        purgeAt: null,
        brief: $brief,
    );

    expect($campaign->brief)->not->toBeNull()
        ->and($campaign->brief->text)->toBe('Reconstituted brief');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php`
Expected: FAIL — parameter brief does not exist

- [ ] **Step 3: Modify Campaign entity to include brief**

Modify `app/Domain/Campaign/Entities/Campaign.php`:

Add `use App\Domain\Campaign\ValueObjects\CampaignBrief;` to imports.

Add `public ?CampaignBrief $brief` parameter to the constructor (before `$domainEvents`):

```php
public function __construct(
    public Uuid $id,
    public Uuid $organizationId,
    public Uuid $createdBy,
    public string $name,
    public ?string $description,
    public ?DateTimeImmutable $startsAt,
    public ?DateTimeImmutable $endsAt,
    public CampaignStatus $status,
    public array $tags,
    public DateTimeImmutable $createdAt,
    public DateTimeImmutable $updatedAt,
    public ?DateTimeImmutable $deletedAt,
    public ?DateTimeImmutable $purgeAt,
    public ?CampaignBrief $brief = null,
    public array $domainEvents = [],
) {}
```

Update `create()` — add `?CampaignBrief $brief = null` parameter and pass to constructor:

```php
public static function create(
    Uuid $organizationId,
    Uuid $createdBy,
    string $name,
    ?string $description = null,
    ?DateTimeImmutable $startsAt = null,
    ?DateTimeImmutable $endsAt = null,
    array $tags = [],
    ?CampaignBrief $brief = null,
): self {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    return new self(
        id: $id,
        organizationId: $organizationId,
        createdBy: $createdBy,
        name: $name,
        description: $description,
        startsAt: $startsAt,
        endsAt: $endsAt,
        status: CampaignStatus::Draft,
        tags: $tags,
        createdAt: $now,
        updatedAt: $now,
        deletedAt: null,
        purgeAt: null,
        brief: $brief,
        domainEvents: [
            new CampaignCreated(
                aggregateId: (string) $id,
                organizationId: (string) $organizationId,
                userId: (string) $createdBy,
                name: $name,
            ),
        ],
    );
}
```

Update `reconstitute()` — add `?CampaignBrief $brief = null` parameter:

```php
public static function reconstitute(
    Uuid $id,
    Uuid $organizationId,
    Uuid $createdBy,
    string $name,
    ?string $description,
    ?DateTimeImmutable $startsAt,
    ?DateTimeImmutable $endsAt,
    CampaignStatus $status,
    array $tags,
    DateTimeImmutable $createdAt,
    DateTimeImmutable $updatedAt,
    ?DateTimeImmutable $deletedAt,
    ?DateTimeImmutable $purgeAt,
    ?CampaignBrief $brief = null,
): self {
    return new self(
        id: $id,
        organizationId: $organizationId,
        createdBy: $createdBy,
        name: $name,
        description: $description,
        startsAt: $startsAt,
        endsAt: $endsAt,
        status: $status,
        tags: $tags,
        createdAt: $createdAt,
        updatedAt: $updatedAt,
        deletedAt: $deletedAt,
        purgeAt: $purgeAt,
        brief: $brief,
    );
}
```

Update `update()` — add `?CampaignBrief $brief = null` parameter:

```php
public function update(
    ?string $name = null,
    ?string $description = null,
    ?DateTimeImmutable $startsAt = null,
    ?DateTimeImmutable $endsAt = null,
    ?array $tags = null,
    ?CampaignStatus $status = null,
    ?CampaignBrief $brief = null,
): self {
    if ($status !== null && ! $this->status->canTransitionTo($status)) {
        throw new InvalidStatusTransitionException($this->status->value, $status->value);
    }

    $now = new DateTimeImmutable;

    return new self(
        id: $this->id,
        organizationId: $this->organizationId,
        createdBy: $this->createdBy,
        name: $name ?? $this->name,
        description: $description ?? $this->description,
        startsAt: $startsAt ?? $this->startsAt,
        endsAt: $endsAt ?? $this->endsAt,
        status: $status ?? $this->status,
        tags: $tags ?? $this->tags,
        createdAt: $this->createdAt,
        updatedAt: $now,
        deletedAt: $this->deletedAt,
        purgeAt: $this->purgeAt,
        brief: $brief ?? $this->brief,
        domainEvents: $this->domainEvents,
    );
}
```

Update `softDelete()` — add `brief: $this->brief`:

```php
public function softDelete(int $graceDays = 30): self
{
    $now = new DateTimeImmutable;

    return new self(
        id: $this->id,
        organizationId: $this->organizationId,
        createdBy: $this->createdBy,
        name: $this->name,
        description: $this->description,
        startsAt: $this->startsAt,
        endsAt: $this->endsAt,
        status: $this->status,
        tags: $this->tags,
        createdAt: $this->createdAt,
        updatedAt: $now,
        deletedAt: $now,
        purgeAt: $now->modify("+{$graceDays} days"),
        brief: $this->brief,
        domainEvents: $this->domainEvents,
    );
}
```

Update `restore()` — add `brief: $this->brief`:

```php
public function restore(): self
{
    if ($this->deletedAt === null) {
        return $this;
    }

    $now = new DateTimeImmutable;

    return new self(
        id: $this->id,
        organizationId: $this->organizationId,
        createdBy: $this->createdBy,
        name: $this->name,
        description: $this->description,
        startsAt: $this->startsAt,
        endsAt: $this->endsAt,
        status: $this->status,
        tags: $this->tags,
        createdAt: $this->createdAt,
        updatedAt: $now,
        deletedAt: null,
        purgeAt: null,
        brief: $this->brief,
        domainEvents: $this->domainEvents,
    );
}
```

Update `releaseEvents()` — add `brief: $this->brief`:

```php
public function releaseEvents(): self
{
    return new self(
        id: $this->id,
        organizationId: $this->organizationId,
        createdBy: $this->createdBy,
        name: $this->name,
        description: $this->description,
        startsAt: $this->startsAt,
        endsAt: $this->endsAt,
        status: $this->status,
        tags: $this->tags,
        createdAt: $this->createdAt,
        updatedAt: $this->updatedAt,
        deletedAt: $this->deletedAt,
        purgeAt: $this->purgeAt,
        brief: $this->brief,
    );
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php`
Expected: 5 tests PASS

- [ ] **Step 5: Run existing Campaign tests to verify no regressions**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Domain/Campaign/ tests/Feature/Campaign/`
Expected: All existing tests PASS (brief defaults to null, backward compatible)

- [ ] **Step 6: Commit**

```bash
git add app/Domain/Campaign/Entities/Campaign.php tests/Unit/Domain/Campaign/Entities/CampaignBriefIntegrationTest.php
git commit -m "feat(campaign): add CampaignBrief to Campaign entity (all factory methods)"
```

---

## Chunk 2: Infrastructure Layer (Migration, Model, Repository, Requests, Resource)

### Task 4: Database migration

**Files:**
- Create: `database/migrations/0001_01_01_000080_add_brief_fields_to_campaigns_table.php`

- [ ] **Step 1: Create the migration**

Note: Do NOT use `->after()` — SQLite (test DB) does not support column ordering.

```php
<?php
// database/migrations/0001_01_01_000080_add_brief_fields_to_campaigns_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('brief_text')->nullable();
            $table->string('brief_target_audience', 500)->nullable();
            $table->text('brief_restrictions')->nullable();
            $table->string('brief_cta', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'brief_text',
                'brief_target_audience',
                'brief_restrictions',
                'brief_cta',
            ]);
        });
    }
};
```

- [ ] **Step 2: Commit**

```bash
git add database/migrations/0001_01_01_000080_add_brief_fields_to_campaigns_table.php
git commit -m "feat(campaign): add brief columns to campaigns table"
```

---

### Task 5: Update CampaignModel

**Files:**
- Modify: `app/Infrastructure/Campaign/Models/CampaignModel.php`

- [ ] **Step 1: Add brief fields to $fillable**

Add `'brief_text'`, `'brief_target_audience'`, `'brief_restrictions'`, `'brief_cta'` to the `$fillable` array:

```php
protected $fillable = [
    'id',
    'organization_id',
    'created_by',
    'name',
    'description',
    'brief_text',
    'brief_target_audience',
    'brief_restrictions',
    'brief_cta',
    'starts_at',
    'ends_at',
    'status',
    'tags',
    'deleted_at',
    'purge_at',
];
```

- [ ] **Step 2: Commit**

```bash
git add app/Infrastructure/Campaign/Models/CampaignModel.php
git commit -m "feat(campaign): add brief fields to CampaignModel fillable"
```

---

### Task 6: Update EloquentCampaignRepository

**Files:**
- Modify: `app/Infrastructure/Campaign/Repositories/EloquentCampaignRepository.php`

- [ ] **Step 1: Update toDomain() to hydrate CampaignBrief**

Add `use App\Domain\Campaign\ValueObjects\CampaignBrief;` to imports.

Update `toDomain()` method — build CampaignBrief from model attributes and pass to `reconstitute()`:

```php
private function toDomain(CampaignModel $model): Campaign
{
    $brief = null;
    if ($model->getAttribute('brief_text') !== null
        || $model->getAttribute('brief_target_audience') !== null
        || $model->getAttribute('brief_restrictions') !== null
        || $model->getAttribute('brief_cta') !== null
    ) {
        $brief = new CampaignBrief(
            text: $model->getAttribute('brief_text'),
            targetAudience: $model->getAttribute('brief_target_audience'),
            restrictions: $model->getAttribute('brief_restrictions'),
            cta: $model->getAttribute('brief_cta'),
        );
    }

    return Campaign::reconstitute(
        id: Uuid::fromString($model->getAttribute('id')),
        organizationId: Uuid::fromString($model->getAttribute('organization_id')),
        createdBy: Uuid::fromString($model->getAttribute('created_by')),
        name: $model->getAttribute('name'),
        description: $model->getAttribute('description'),
        startsAt: $model->getAttribute('starts_at')
            ? new DateTimeImmutable($model->getAttribute('starts_at')->toDateTimeString())
            : null,
        endsAt: $model->getAttribute('ends_at')
            ? new DateTimeImmutable($model->getAttribute('ends_at')->toDateTimeString())
            : null,
        status: CampaignStatus::from($model->getAttribute('status')),
        tags: $model->getAttribute('tags') ?? [],
        createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
        updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        deletedAt: $model->getAttribute('deleted_at')
            ? new DateTimeImmutable($model->getAttribute('deleted_at')->toDateTimeString())
            : null,
        purgeAt: $model->getAttribute('purge_at')
            ? new DateTimeImmutable($model->getAttribute('purge_at')->toDateTimeString())
            : null,
        brief: $brief,
    );
}
```

- [ ] **Step 2: Update toArray() to include brief fields**

```php
private function toArray(Campaign $campaign): array
{
    return [
        'id' => (string) $campaign->id,
        'organization_id' => (string) $campaign->organizationId,
        'created_by' => (string) $campaign->createdBy,
        'name' => $campaign->name,
        'description' => $campaign->description,
        'brief_text' => $campaign->brief?->text,
        'brief_target_audience' => $campaign->brief?->targetAudience,
        'brief_restrictions' => $campaign->brief?->restrictions,
        'brief_cta' => $campaign->brief?->cta,
        'starts_at' => $campaign->startsAt?->format('Y-m-d H:i:s'),
        'ends_at' => $campaign->endsAt?->format('Y-m-d H:i:s'),
        'status' => $campaign->status->value,
        'tags' => $campaign->tags,
        'deleted_at' => $campaign->deletedAt?->format('Y-m-d H:i:s'),
        'purge_at' => $campaign->purgeAt?->format('Y-m-d H:i:s'),
    ];
}
```

- [ ] **Step 3: Update toDomainFromCached() to hydrate CampaignBrief**

```php
private function toDomainFromCached(array $data): Campaign
{
    $brief = null;
    if (($data['brief_text'] ?? null) !== null
        || ($data['brief_target_audience'] ?? null) !== null
        || ($data['brief_restrictions'] ?? null) !== null
        || ($data['brief_cta'] ?? null) !== null
    ) {
        $brief = new CampaignBrief(
            text: $data['brief_text'] ?? null,
            targetAudience: $data['brief_target_audience'] ?? null,
            restrictions: $data['brief_restrictions'] ?? null,
            cta: $data['brief_cta'] ?? null,
        );
    }

    return Campaign::reconstitute(
        id: Uuid::fromString($data['id']),
        organizationId: Uuid::fromString($data['organization_id']),
        createdBy: Uuid::fromString($data['created_by']),
        name: $data['name'],
        description: $data['description'] ?? null,
        startsAt: isset($data['starts_at'])
            ? new DateTimeImmutable($data['starts_at'])
            : null,
        endsAt: isset($data['ends_at'])
            ? new DateTimeImmutable($data['ends_at'])
            : null,
        status: CampaignStatus::from($data['status']),
        tags: $data['tags'] ?? [],
        createdAt: new DateTimeImmutable($data['created_at']),
        updatedAt: new DateTimeImmutable($data['updated_at']),
        deletedAt: isset($data['deleted_at'])
            ? new DateTimeImmutable($data['deleted_at'])
            : null,
        purgeAt: isset($data['purge_at'])
            ? new DateTimeImmutable($data['purge_at'])
            : null,
        brief: $brief,
    );
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Infrastructure/Campaign/Repositories/EloquentCampaignRepository.php
git commit -m "feat(campaign): update repository to persist and hydrate CampaignBrief"
```

---

### Task 7: Update Campaign DTOs

**Files:**
- Modify: `app/Application/Campaign/DTOs/CreateCampaignInput.php`
- Modify: `app/Application/Campaign/DTOs/UpdateCampaignInput.php`
- Modify: `app/Application/Campaign/DTOs/CampaignOutput.php`

- [ ] **Step 1: Add brief fields to CreateCampaignInput**

```php
<?php
// app/Application/Campaign/DTOs/CreateCampaignInput.php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class CreateCampaignInput
{
    /**
     * @param  string[]  $tags
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $name,
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public array $tags = [],
        public ?string $briefText = null,
        public ?string $briefTargetAudience = null,
        public ?string $briefRestrictions = null,
        public ?string $briefCta = null,
    ) {}
}
```

- [ ] **Step 2: Add brief fields + clearBrief to UpdateCampaignInput**

```php
<?php
// app/Application/Campaign/DTOs/UpdateCampaignInput.php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

final readonly class UpdateCampaignInput
{
    /**
     * @param  string[]|null  $tags
     */
    public function __construct(
        public string $organizationId,
        public string $campaignId,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?array $tags = null,
        public ?string $status = null,
        public ?string $briefText = null,
        public ?string $briefTargetAudience = null,
        public ?string $briefRestrictions = null,
        public ?string $briefCta = null,
        public bool $clearBrief = false,
    ) {}
}
```

- [ ] **Step 3: Add brief to CampaignOutput**

```php
<?php
// app/Application/Campaign/DTOs/CampaignOutput.php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

use App\Domain\Campaign\Entities\Campaign;

final readonly class CampaignOutput
{
    /**
     * @param  string[]  $tags
     * @param  array<string, int>|null  $stats
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $description,
        public ?string $startsAt,
        public ?string $endsAt,
        public string $status,
        public array $tags,
        public ?array $stats,
        public string $createdAt,
        public string $updatedAt,
        public ?string $briefText = null,
        public ?string $briefTargetAudience = null,
        public ?string $briefRestrictions = null,
        public ?string $briefCta = null,
    ) {}

    /**
     * @param  array<string, int>|null  $stats
     */
    public static function fromEntity(Campaign $campaign, ?array $stats = null): self
    {
        return new self(
            id: (string) $campaign->id,
            organizationId: (string) $campaign->organizationId,
            name: $campaign->name,
            description: $campaign->description,
            startsAt: $campaign->startsAt?->format('c'),
            endsAt: $campaign->endsAt?->format('c'),
            status: $campaign->status->value,
            tags: $campaign->tags,
            stats: $stats,
            createdAt: $campaign->createdAt->format('c'),
            updatedAt: $campaign->updatedAt->format('c'),
            briefText: $campaign->brief?->text,
            briefTargetAudience: $campaign->brief?->targetAudience,
            briefRestrictions: $campaign->brief?->restrictions,
            briefCta: $campaign->brief?->cta,
        );
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Application/Campaign/DTOs/CreateCampaignInput.php app/Application/Campaign/DTOs/UpdateCampaignInput.php app/Application/Campaign/DTOs/CampaignOutput.php
git commit -m "feat(campaign): add brief fields to Campaign DTOs"
```

---

### Task 8: Update Campaign Use Cases

**Files:**
- Modify: `app/Application/Campaign/UseCases/CreateCampaignUseCase.php`
- Modify: `app/Application/Campaign/UseCases/UpdateCampaignUseCase.php`

- [ ] **Step 1: Update CreateCampaignUseCase to build CampaignBrief**

Add `use App\Domain\Campaign\ValueObjects\CampaignBrief;` to imports.

Replace the `Campaign::create()` call to include brief:

```php
public function execute(CreateCampaignInput $input): CampaignOutput
{
    if ($this->campaignRepository->existsByOrganizationAndName(
        Uuid::fromString($input->organizationId),
        $input->name,
    )) {
        throw new DuplicateCampaignNameException($input->name);
    }

    $brief = null;
    if ($input->briefText !== null
        || $input->briefTargetAudience !== null
        || $input->briefRestrictions !== null
        || $input->briefCta !== null
    ) {
        $brief = new CampaignBrief(
            text: $input->briefText,
            targetAudience: $input->briefTargetAudience,
            restrictions: $input->briefRestrictions,
            cta: $input->briefCta,
        );
    }

    $campaign = Campaign::create(
        organizationId: Uuid::fromString($input->organizationId),
        createdBy: Uuid::fromString($input->userId),
        name: $input->name,
        description: $input->description,
        startsAt: $input->startsAt !== null ? new DateTimeImmutable($input->startsAt) : null,
        endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
        tags: $input->tags,
        brief: $brief,
    );

    $this->campaignRepository->create($campaign);
    $this->eventDispatcher->dispatch(...$campaign->domainEvents);

    return CampaignOutput::fromEntity($campaign);
}
```

- [ ] **Step 2: Update UpdateCampaignUseCase to handle brief + clearBrief**

Add `use App\Domain\Campaign\ValueObjects\CampaignBrief;` to imports.

Update the `execute()` method — after the name check and before `$campaign->update()`:

```php
public function execute(UpdateCampaignInput $input): CampaignOutput
{
    $campaign = $this->campaignRepository->findById(Uuid::fromString($input->campaignId));

    if ($campaign === null || (string) $campaign->organizationId !== $input->organizationId || $campaign->isDeleted()) {
        throw new CampaignNotFoundException($input->campaignId);
    }

    if ($input->name !== null && strtolower($input->name) !== strtolower($campaign->name)) {
        if ($this->campaignRepository->existsByOrganizationAndName(
            $campaign->organizationId,
            $input->name,
            $campaign->id,
        )) {
            throw new DuplicateCampaignNameException($input->name);
        }
    }

    // Resolve brief: clearBrief > new brief fields > keep existing
    $brief = null;
    if ($input->clearBrief) {
        // Explicit clear: set empty brief (will be stored as nulls)
        $brief = new CampaignBrief(null, null, null, null);
    } elseif ($input->briefText !== null
        || $input->briefTargetAudience !== null
        || $input->briefRestrictions !== null
        || $input->briefCta !== null
    ) {
        // New brief fields provided: merge with existing
        $inputBrief = new CampaignBrief(
            text: $input->briefText,
            targetAudience: $input->briefTargetAudience,
            restrictions: $input->briefRestrictions,
            cta: $input->briefCta,
        );
        $brief = $campaign->brief !== null
            ? $campaign->brief->mergeWith($inputBrief)
            : $inputBrief;
    }
    // If $brief is null here, Campaign::update() will preserve existing via ?? coalescing

    $campaign = $campaign->update(
        name: $input->name,
        description: $input->description,
        startsAt: $input->startsAt !== null ? new DateTimeImmutable($input->startsAt) : null,
        endsAt: $input->endsAt !== null ? new DateTimeImmutable($input->endsAt) : null,
        tags: $input->tags,
        status: $input->status !== null ? CampaignStatus::from($input->status) : null,
        brief: $brief,
    );

    $this->campaignRepository->update($campaign);

    return CampaignOutput::fromEntity($campaign);
}
```

- [ ] **Step 3: Update DuplicateCampaignUseCase to propagate brief**

In `app/Application/Campaign/UseCases/DuplicateCampaignUseCase.php`, find the `Campaign::create()` call and add `brief: $original->brief` to propagate the brief when duplicating a campaign.

- [ ] **Step 4: Commit**

```bash
git add app/Application/Campaign/UseCases/CreateCampaignUseCase.php app/Application/Campaign/UseCases/UpdateCampaignUseCase.php app/Application/Campaign/UseCases/DuplicateCampaignUseCase.php
git commit -m "feat(campaign): handle CampaignBrief in create/update/duplicate use cases"
```

---

### Task 9: Update Form Requests and Controller

**Files:**
- Modify: `app/Infrastructure/Campaign/Requests/CreateCampaignRequest.php`
- Modify: `app/Infrastructure/Campaign/Requests/UpdateCampaignRequest.php`
- Modify: `app/Infrastructure/Campaign/Controllers/CampaignController.php`
- Modify: `app/Infrastructure/Campaign/Resources/CampaignResource.php`

- [ ] **Step 1: Add brief validation to CreateCampaignRequest**

Add to the `rules()` return array:

```php
'brief_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
'brief_target_audience' => ['sometimes', 'nullable', 'string', 'max:500'],
'brief_restrictions' => ['sometimes', 'nullable', 'string', 'max:2000'],
'brief_cta' => ['sometimes', 'nullable', 'string', 'max:500'],
```

- [ ] **Step 2: Add brief validation to UpdateCampaignRequest**

Add to the `rules()` return array:

```php
'brief_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
'brief_target_audience' => ['sometimes', 'nullable', 'string', 'max:500'],
'brief_restrictions' => ['sometimes', 'nullable', 'string', 'max:2000'],
'brief_cta' => ['sometimes', 'nullable', 'string', 'max:500'],
'clear_brief' => ['sometimes', 'boolean'],
```

- [ ] **Step 3: Update CampaignController store() to pass brief fields**

In `store()`, add to the `CreateCampaignInput` constructor:

```php
$output = $useCase->execute(new CreateCampaignInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    userId: $request->attributes->get('auth_user_id'),
    name: $request->validated('name'),
    description: $request->validated('description'),
    startsAt: $request->validated('starts_at'),
    endsAt: $request->validated('ends_at'),
    tags: $request->validated('tags', []),
    briefText: $request->validated('brief_text'),
    briefTargetAudience: $request->validated('brief_target_audience'),
    briefRestrictions: $request->validated('brief_restrictions'),
    briefCta: $request->validated('brief_cta'),
));
```

- [ ] **Step 4: Update CampaignController update() to pass brief fields**

In `update()`, add to the `UpdateCampaignInput` constructor:

```php
$output = $useCase->execute(new UpdateCampaignInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    campaignId: $id,
    name: $request->validated('name'),
    description: $request->validated('description'),
    startsAt: $request->validated('starts_at'),
    endsAt: $request->validated('ends_at'),
    tags: $request->validated('tags'),
    status: $request->validated('status'),
    briefText: $request->validated('brief_text'),
    briefTargetAudience: $request->validated('brief_target_audience'),
    briefRestrictions: $request->validated('brief_restrictions'),
    briefCta: $request->validated('brief_cta'),
    clearBrief: (bool) $request->validated('clear_brief', false),
));
```

- [ ] **Step 5: Update CampaignResource to include brief**

Update constructor and `fromOutput()`:

```php
final readonly class CampaignResource
{
    /**
     * @param  string[]  $tags
     * @param  array<string, int>|null  $stats
     */
    private function __construct(
        private string $id,
        private string $name,
        private ?string $description,
        private ?string $startsAt,
        private ?string $endsAt,
        private string $status,
        private array $tags,
        private ?array $stats,
        private string $createdAt,
        private string $updatedAt,
        private ?string $briefText,
        private ?string $briefTargetAudience,
        private ?string $briefRestrictions,
        private ?string $briefCta,
    ) {}

    public static function fromOutput(CampaignOutput $output): self
    {
        return new self(
            id: $output->id,
            name: $output->name,
            description: $output->description,
            startsAt: $output->startsAt,
            endsAt: $output->endsAt,
            status: $output->status,
            tags: $output->tags,
            stats: $output->stats,
            createdAt: $output->createdAt,
            updatedAt: $output->updatedAt,
            briefText: $output->briefText,
            briefTargetAudience: $output->briefTargetAudience,
            briefRestrictions: $output->briefRestrictions,
            briefCta: $output->briefCta,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'campaign',
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'brief' => [
                    'text' => $this->briefText,
                    'target_audience' => $this->briefTargetAudience,
                    'restrictions' => $this->briefRestrictions,
                    'cta' => $this->briefCta,
                ],
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
                'status' => $this->status,
                'tags' => $this->tags,
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
        ];

        if ($this->stats !== null) {
            $data['attributes']['stats'] = $this->stats;
        }

        return $data;
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Infrastructure/Campaign/Requests/CreateCampaignRequest.php app/Infrastructure/Campaign/Requests/UpdateCampaignRequest.php app/Infrastructure/Campaign/Controllers/CampaignController.php app/Infrastructure/Campaign/Resources/CampaignResource.php
git commit -m "feat(campaign): wire brief fields through requests, controller, and resource"
```

---

### Task 10: Campaign Brief feature tests

**Files:**
- Create: `tests/Feature/Campaign/CampaignBriefTest.php`

- [ ] **Step 1: Write feature tests**

```php
<?php
// tests/Feature/Campaign/CampaignBriefTest.php

declare(strict_types=1);

use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates campaign with brief fields — 201', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Black Friday Campaign',
        'brief_text' => 'Promote Black Friday discounts for fashion store',
        'brief_target_audience' => 'Young adults 18-30',
        'brief_restrictions' => 'No aggressive language',
        'brief_cta' => 'Shop now with 50% off',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.brief.text', 'Promote Black Friday discounts for fashion store')
        ->assertJsonPath('data.attributes.brief.target_audience', 'Young adults 18-30')
        ->assertJsonPath('data.attributes.brief.restrictions', 'No aggressive language')
        ->assertJsonPath('data.attributes.brief.cta', 'Shop now with 50% off');
});

it('creates campaign without brief — 201', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Simple Campaign',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.target_audience', null);
});

it('updates campaign brief — 200', function () {
    // Create campaign first
    $createResponse = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Update Brief Test',
    ]);

    $campaignId = $createResponse->json('data.id');

    // Update with brief
    $response = $this->withHeaders($this->headers)->putJson("/api/v1/campaigns/{$campaignId}", [
        'brief_text' => 'Updated brief text',
        'brief_cta' => 'Buy now',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', 'Updated brief text')
        ->assertJsonPath('data.attributes.brief.cta', 'Buy now');
});

it('updates campaign brief merges with existing — 200', function () {
    // Create with full brief
    $createResponse = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Merge Brief Test',
        'brief_text' => 'Original text',
        'brief_target_audience' => 'Teens',
        'brief_cta' => 'Original CTA',
    ]);

    $campaignId = $createResponse->json('data.id');

    // Update only CTA — text and audience should be preserved
    $response = $this->withHeaders($this->headers)->putJson("/api/v1/campaigns/{$campaignId}", [
        'brief_cta' => 'New CTA',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', 'Original text')
        ->assertJsonPath('data.attributes.brief.target_audience', 'Teens')
        ->assertJsonPath('data.attributes.brief.cta', 'New CTA');
});

it('clears campaign brief with clear_brief flag — 200', function () {
    // Create with brief
    $createResponse = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Clear Brief Test',
        'brief_text' => 'Will be cleared',
    ]);

    $campaignId = $createResponse->json('data.id');

    // Clear brief
    $response = $this->withHeaders($this->headers)->putJson("/api/v1/campaigns/{$campaignId}", [
        'clear_brief' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.cta', null);
});

it('clear_brief prevails over brief fields sent simultaneously — 200', function () {
    $createResponse = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Clear Prevails Test',
        'brief_text' => 'Will be cleared',
        'brief_cta' => 'Buy now',
    ]);

    $campaignId = $createResponse->json('data.id');

    $response = $this->withHeaders($this->headers)->putJson("/api/v1/campaigns/{$campaignId}", [
        'clear_brief' => true,
        'brief_text' => 'Should be ignored',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.brief.text', null)
        ->assertJsonPath('data.attributes.brief.cta', null);
});

it('validates brief_text max length — 422', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/campaigns', [
        'name' => 'Validation Test',
        'brief_text' => str_repeat('a', 2001),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('brief_text');
});
```

- [ ] **Step 2: Run tests**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Feature/Campaign/CampaignBriefTest.php`
Expected: 6 tests PASS

- [ ] **Step 3: Run all Campaign tests to verify no regressions**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Feature/Campaign/`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Campaign/CampaignBriefTest.php
git commit -m "test(campaign): add feature tests for Campaign Brief CRUD"
```

---

## Chunk 3: AI Generation Pipeline (DTOs, Requests, UseCases, Controller)

### Task 11: Update AI Generation Input DTOs + Form Requests (atomic)

**IMPORTANT:** DTO changes and Form Request changes MUST be deployed together. The DTO `$topic` default change requires the Form Request conditional validation to be in place simultaneously.

**Files:**
- Modify: `app/Application/ContentAI/DTOs/GenerateTitleInput.php`
- Modify: `app/Application/ContentAI/DTOs/GenerateDescriptionInput.php`
- Modify: `app/Application/ContentAI/DTOs/GenerateHashtagsInput.php`
- Modify: `app/Application/ContentAI/DTOs/GenerateFullContentInput.php`
- Modify: `app/Infrastructure/ContentAI/Requests/GenerateTitleRequest.php`
- Modify: `app/Infrastructure/ContentAI/Requests/GenerateDescriptionRequest.php`
- Modify: `app/Infrastructure/ContentAI/Requests/GenerateHashtagsRequest.php`
- Modify: `app/Infrastructure/ContentAI/Requests/GenerateFullContentRequest.php`

- [ ] **Step 1: Add campaignId and generationMode to all 4 DTOs**

Add to each DTO constructor (after existing params, before closing parenthesis):

**GenerateTitleInput.php:**
```php
public function __construct(
    public string $organizationId,
    public string $userId,
    public string $topic = '',
    public ?string $socialNetwork = null,
    public ?string $tone = null,
    public ?string $language = null,
    public ?string $campaignId = null,
    public string $generationMode = 'fields_only',
) {}
```

**GenerateDescriptionInput.php:**
```php
public function __construct(
    public string $organizationId,
    public string $userId,
    public string $topic = '',
    public ?string $socialNetwork = null,
    public ?string $tone = null,
    public array $keywords = [],
    public ?string $language = null,
    public ?string $campaignId = null,
    public string $generationMode = 'fields_only',
) {}
```

**GenerateHashtagsInput.php:**
```php
public function __construct(
    public string $organizationId,
    public string $userId,
    public string $topic = '',
    public ?string $niche = null,
    public ?string $socialNetwork = null,
    public ?string $campaignId = null,
    public string $generationMode = 'fields_only',
) {}
```

**GenerateFullContentInput.php:**
```php
public function __construct(
    public string $organizationId,
    public string $userId,
    public string $topic = '',
    public array $socialNetworks = [],
    public ?string $tone = null,
    public array $keywords = [],
    public ?string $language = null,
    public ?string $campaignId = null,
    public string $generationMode = 'fields_only',
) {}
```

Note: `$socialNetworks` gets a default `= []` for PHP syntax reasons (params with defaults cannot precede required params). The Form Request still enforces `social_networks` as `required` in ALL modes — even `brief_only`, because the brief alone cannot specify target networks.

- [ ] **Step 2: Update all 4 Form Requests with conditional validation**

See the updated rules below for each request. Key changes:
- Add `generation_mode` and `campaign_id` validation
- `topic` becomes conditional based on mode
- `campaign_id` is `required` (NOT nullable) when mode != `fields_only`

**GenerateTitleRequest.php** — replace `rules()`:

```php
public function rules(): array
{
    $mode = $this->input('generation_mode', 'fields_only');

    return [
        'generation_mode' => ['sometimes', 'string', 'in:fields_only,brief_only,brief_and_fields'],
        'campaign_id' => [$mode !== 'fields_only' ? 'required' : 'sometimes', 'string', 'uuid'],
        'topic' => [$mode === 'brief_only' ? 'sometimes' : 'required', 'string', 'min:10', 'max:500'],
        'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
        'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
        'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
    ];
}
```

**GenerateDescriptionRequest.php** — replace `rules()`:

```php
public function rules(): array
{
    $mode = $this->input('generation_mode', 'fields_only');

    return [
        'generation_mode' => ['sometimes', 'string', 'in:fields_only,brief_only,brief_and_fields'],
        'campaign_id' => [$mode !== 'fields_only' ? 'required' : 'sometimes', 'string', 'uuid'],
        'topic' => [$mode === 'brief_only' ? 'sometimes' : 'required', 'string', 'min:10', 'max:500'],
        'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
        'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
        'keywords' => ['sometimes', 'array', 'max:10'],
        'keywords.*' => ['string', 'min:1', 'max:50'],
        'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
    ];
}
```

**GenerateHashtagsRequest.php** — replace `rules()`:

```php
public function rules(): array
{
    $mode = $this->input('generation_mode', 'fields_only');

    return [
        'generation_mode' => ['sometimes', 'string', 'in:fields_only,brief_only,brief_and_fields'],
        'campaign_id' => [$mode !== 'fields_only' ? 'required' : 'sometimes', 'string', 'uuid'],
        'topic' => [$mode === 'brief_only' ? 'sometimes' : 'required', 'string', 'min:10', 'max:500'],
        'niche' => ['sometimes', 'nullable', 'string', 'min:3', 'max:100'],
        'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
    ];
}
```

**GenerateFullContentRequest.php** — replace `rules()`:

```php
public function rules(): array
{
    $mode = $this->input('generation_mode', 'fields_only');

    return [
        'generation_mode' => ['sometimes', 'string', 'in:fields_only,brief_only,brief_and_fields'],
        'campaign_id' => [$mode !== 'fields_only' ? 'required' : 'sometimes', 'string', 'uuid'],
        'topic' => [$mode === 'brief_only' ? 'sometimes' : 'required', 'string', 'min:10', 'max:500'],
        'social_networks' => ['required', 'array', 'min:1', 'max:5'],
        'social_networks.*' => ['string', 'in:instagram,tiktok,youtube'],
        'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
        'keywords' => ['sometimes', 'array', 'max:10'],
        'keywords.*' => ['string', 'min:1', 'max:50'],
        'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
    ];
}
```

- [ ] **Step 4: Commit (DTOs + Form Requests together)**

```bash
git add app/Application/ContentAI/DTOs/GenerateTitleInput.php app/Application/ContentAI/DTOs/GenerateDescriptionInput.php app/Application/ContentAI/DTOs/GenerateHashtagsInput.php app/Application/ContentAI/DTOs/GenerateFullContentInput.php app/Infrastructure/ContentAI/Requests/GenerateTitleRequest.php app/Infrastructure/ContentAI/Requests/GenerateDescriptionRequest.php app/Infrastructure/ContentAI/Requests/GenerateHashtagsRequest.php app/Infrastructure/ContentAI/Requests/GenerateFullContentRequest.php
git commit -m "feat(content-ai): add generation_mode and campaign_id to DTOs and Form Requests"
```

---

### Task 12: Create BriefContextResolver service

**Files:**
- Create: `app/Application/ContentAI/Services/BriefContextResolver.php`
- Create: `tests/Unit/Application/ContentAI/Services/BriefContextResolverTest.php`

This service extracts the brief resolution logic to avoid duplicating it in all 4 UseCases.

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Unit/Application/ContentAI/Services/BriefContextResolverTest.php

declare(strict_types=1);

use App\Application\ContentAI\Services\BriefContextResolver;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Exceptions\CampaignBriefRequiredException;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

function makeCampaignWithBrief(string $orgId, ?CampaignBrief $brief = null): Campaign
{
    return Campaign::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($orgId),
        createdBy: Uuid::generate(),
        name: 'Test Campaign',
        description: null,
        startsAt: null,
        endsAt: null,
        status: CampaignStatus::Draft,
        tags: [],
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        deletedAt: null,
        purgeAt: null,
        brief: $brief,
    );
}

it('returns original topic when mode is fields_only', function () {
    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $resolver = new BriefContextResolver($repo);

    $result = $resolver->resolve('fields_only', null, 'org-id', 'My topic');

    expect($result)->toBe('My topic');
    $repo->shouldNotHaveBeenCalled();
});

it('returns brief context as topic when mode is brief_only', function () {
    $orgId = (string) Uuid::generate();
    $brief = new CampaignBrief('Black Friday campaign', 'Teens', null, null);
    $campaign = makeCampaignWithBrief($orgId, $brief);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);
    $result = $resolver->resolve('brief_only', (string) $campaign->id, $orgId, '');

    expect($result)
        ->toContain('[CAMPAIGN BRIEF]')
        ->toContain('Objective: Black Friday campaign')
        ->toContain('Target Audience: Teens');
});

it('returns brief + topic when mode is brief_and_fields', function () {
    $orgId = (string) Uuid::generate();
    $brief = new CampaignBrief('Campaign context', null, null, null);
    $campaign = makeCampaignWithBrief($orgId, $brief);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);
    $result = $resolver->resolve('brief_and_fields', (string) $campaign->id, $orgId, 'User topic here');

    expect($result)
        ->toContain('[CAMPAIGN BRIEF]')
        ->toContain('Objective: Campaign context')
        ->toContain("[USER TOPIC]\nUser topic here");
});

it('throws CampaignNotFoundException when campaign not found', function () {
    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $resolver = new BriefContextResolver($repo);
    $resolver->resolve('brief_only', (string) Uuid::generate(), (string) Uuid::generate(), '');
})->throws(CampaignNotFoundException::class);

it('throws CampaignNotFoundException when campaign belongs to another org', function () {
    $campaignOrgId = (string) Uuid::generate();
    $requestOrgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($campaignOrgId, new CampaignBrief('Brief', null, null, null));

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);
    $resolver->resolve('brief_only', (string) $campaign->id, $requestOrgId, '');
})->throws(CampaignNotFoundException::class);

it('throws CampaignBriefRequiredException when campaign has no brief in brief_only mode', function () {
    $orgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($orgId, null);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);
    $resolver->resolve('brief_only', (string) $campaign->id, $orgId, '');
})->throws(CampaignBriefRequiredException::class);

it('throws CampaignBriefRequiredException when campaign has no brief in brief_and_fields mode', function () {
    $orgId = (string) Uuid::generate();
    $campaign = makeCampaignWithBrief($orgId, null);

    $repo = Mockery::mock(CampaignRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($campaign);

    $resolver = new BriefContextResolver($repo);
    $resolver->resolve('brief_and_fields', (string) $campaign->id, $orgId, 'Some topic');
})->throws(CampaignBriefRequiredException::class);
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Application/ContentAI/Services/BriefContextResolverTest.php`
Expected: FAIL — class BriefContextResolver not found

- [ ] **Step 3: Create BriefContextResolver**

```php
<?php
// app/Application/ContentAI/Services/BriefContextResolver.php

declare(strict_types=1);

namespace App\Application\ContentAI\Services;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Exceptions\CampaignBriefRequiredException;
use App\Domain\Campaign\Exceptions\CampaignNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;

final class BriefContextResolver
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Resolve the topic based on generation mode and campaign brief.
     *
     * @return string The resolved topic (original, brief-only, or brief+topic)
     */
    public function resolve(
        string $generationMode,
        ?string $campaignId,
        string $organizationId,
        string $topic,
    ): string {
        if ($generationMode === 'fields_only') {
            return $topic;
        }

        $campaign = $this->campaignRepository->findById(
            Uuid::fromString($campaignId),
        );

        if ($campaign === null || $campaign->organizationId->toString() !== $organizationId) {
            throw new CampaignNotFoundException($campaignId);
        }

        if ($campaign->brief === null || $campaign->brief->isEmpty()) {
            throw new CampaignBriefRequiredException((string) $campaign->id);
        }

        $briefContext = $campaign->brief->toPromptContext();

        if ($generationMode === 'brief_only') {
            return $briefContext;
        }

        // brief_and_fields
        return $briefContext . "\n\n[USER TOPIC]\n" . $topic;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Application/ContentAI/Services/BriefContextResolverTest.php`
Expected: 6 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Application/ContentAI/Services/BriefContextResolver.php tests/Unit/Application/ContentAI/Services/BriefContextResolverTest.php
git commit -m "feat(content-ai): add BriefContextResolver service for generation pipeline"
```

---

### Task 13: Update GenerateTitleUseCase (and other 3)

**Files:**
- Modify: `app/Application/ContentAI/UseCases/GenerateTitleUseCase.php`
- Modify: `app/Application/ContentAI/UseCases/GenerateDescriptionUseCase.php`
- Modify: `app/Application/ContentAI/UseCases/GenerateHashtagsUseCase.php`
- Modify: `app/Application/ContentAI/UseCases/GenerateFullContentUseCase.php`

- [ ] **Step 1: Update GenerateTitleUseCase**

Add `BriefContextResolver` dependency and resolve topic before calling textGenerator:

```php
<?php

declare(strict_types=1);

namespace App\Application\ContentAI\UseCases;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\AIGenerationOutput;
use App\Application\ContentAI\DTOs\GenerateTitleInput;
use App\Application\ContentAI\Services\BriefContextResolver;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface;
use App\Domain\ContentAI\Entities\AIGeneration;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\ValueObjects\Uuid;

final class GenerateTitleUseCase
{
    public function __construct(
        private readonly TextGeneratorInterface $textGenerator,
        private readonly AIGenerationRepositoryInterface $generationRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BriefContextResolver $briefContextResolver,
    ) {}

    public function execute(GenerateTitleInput $input): AIGenerationOutput
    {
        $topic = $this->briefContextResolver->resolve(
            $input->generationMode,
            $input->campaignId,
            $input->organizationId,
            $input->topic,
        );

        $result = $this->textGenerator->generateTitle(
            topic: $topic,
            socialNetwork: $input->socialNetwork,
            tone: $input->tone,
            language: $input->language,
        );

        $generation = AIGeneration::create(
            organizationId: Uuid::fromString($input->organizationId),
            userId: Uuid::fromString($input->userId),
            type: GenerationType::Title,
            input: [
                'topic' => $input->topic,
                'social_network' => $input->socialNetwork,
                'tone' => $input->tone,
                'language' => $input->language,
                'generation_mode' => $input->generationMode,
                'campaign_id' => $input->campaignId,
            ],
            output: $result->output,
            usage: new AIUsage(
                tokensInput: $result->tokensInput,
                tokensOutput: $result->tokensOutput,
                model: $result->model,
                costEstimate: $result->costEstimate,
                durationMs: $result->durationMs,
            ),
        );

        $this->generationRepository->create($generation);
        $this->eventDispatcher->dispatch(...$generation->domainEvents);

        return AIGenerationOutput::fromEntity($generation);
    }
}
```

- [ ] **Step 2: Update GenerateDescriptionUseCase**

Same pattern — add `BriefContextResolver`, resolve topic, log generation_mode and campaign_id in input array. The key difference is passing `keywords` to `generateDescription`:

```php
$topic = $this->briefContextResolver->resolve(
    $input->generationMode,
    $input->campaignId,
    $input->organizationId,
    $input->topic,
);

$result = $this->textGenerator->generateDescription(
    topic: $topic,
    socialNetwork: $input->socialNetwork,
    tone: $input->tone,
    keywords: $input->keywords,
    language: $input->language,
);
```

Add `'generation_mode' => $input->generationMode, 'campaign_id' => $input->campaignId` to the `input` array in `AIGeneration::create()`.

- [ ] **Step 3: Update GenerateHashtagsUseCase**

Same pattern — resolve topic, pass `niche`:

```php
$topic = $this->briefContextResolver->resolve(
    $input->generationMode,
    $input->campaignId,
    $input->organizationId,
    $input->topic,
);

$result = $this->textGenerator->generateHashtags(
    topic: $topic,
    niche: $input->niche,
    socialNetwork: $input->socialNetwork,
);
```

- [ ] **Step 4: Update GenerateFullContentUseCase**

Same pattern — resolve topic:

```php
$topic = $this->briefContextResolver->resolve(
    $input->generationMode,
    $input->campaignId,
    $input->organizationId,
    $input->topic,
);

$result = $this->textGenerator->generateFullContent(
    topic: $topic,
    socialNetworks: $input->socialNetworks,
    tone: $input->tone,
    keywords: $input->keywords,
    language: $input->language,
);
```

- [ ] **Step 5: Update existing AI tests to provide BriefContextResolver**

All 4 generation UseCases now have a new constructor parameter (`BriefContextResolver`). Existing unit tests that construct these UseCases directly will fail.

**For unit tests** (that use `new GenerateTitleUseCase(...)` directly), add `BriefContextResolver` as 4th constructor arg:

```php
use App\Application\ContentAI\Services\BriefContextResolver;
use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;

// In beforeEach or test setup:
$campaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
$briefResolver = new BriefContextResolver($campaignRepo);
// Pass $briefResolver as 4th arg to UseCase constructor
```

**For feature tests** (that resolve UseCases from Laravel container), bind the mock repo:

```php
$campaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
$this->app->instance(CampaignRepositoryInterface::class, $campaignRepo);
```

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Unit/Application/ContentAI/ tests/Unit/Infrastructure/ContentAI/`
Expected: All existing tests PASS after adding the mock.

- [ ] **Step 6: Commit**

```bash
git add app/Application/ContentAI/UseCases/GenerateTitleUseCase.php app/Application/ContentAI/UseCases/GenerateDescriptionUseCase.php app/Application/ContentAI/UseCases/GenerateHashtagsUseCase.php app/Application/ContentAI/UseCases/GenerateFullContentUseCase.php
git commit -m "feat(content-ai): integrate BriefContextResolver into all generation use cases"
```

---

### Task 14: Update AIController to pass new fields

**Files:**
- Modify: `app/Infrastructure/ContentAI/Controllers/AIController.php`

- [ ] **Step 1: Update all 4 generation methods**

Add `campaignId` and `generationMode` to all 4 DTO constructions.

**generateTitle:**
```php
$output = $useCase->execute(new GenerateTitleInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    userId: $request->attributes->get('auth_user_id'),
    topic: $request->validated('topic', ''),
    socialNetwork: $request->validated('social_network'),
    tone: $request->validated('tone'),
    language: $request->validated('language'),
    campaignId: $request->validated('campaign_id'),
    generationMode: $request->validated('generation_mode', 'fields_only'),
));
```

**generateDescription:**
```php
$output = $useCase->execute(new GenerateDescriptionInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    userId: $request->attributes->get('auth_user_id'),
    topic: $request->validated('topic', ''),
    socialNetwork: $request->validated('social_network'),
    tone: $request->validated('tone'),
    keywords: $request->validated('keywords', []),
    language: $request->validated('language'),
    campaignId: $request->validated('campaign_id'),
    generationMode: $request->validated('generation_mode', 'fields_only'),
));
```

**generateHashtags:**
```php
$output = $useCase->execute(new GenerateHashtagsInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    userId: $request->attributes->get('auth_user_id'),
    topic: $request->validated('topic', ''),
    niche: $request->validated('niche'),
    socialNetwork: $request->validated('social_network'),
    campaignId: $request->validated('campaign_id'),
    generationMode: $request->validated('generation_mode', 'fields_only'),
));
```

**generateContent:**
```php
$output = $useCase->execute(new GenerateFullContentInput(
    organizationId: $request->attributes->get('auth_organization_id'),
    userId: $request->attributes->get('auth_user_id'),
    topic: $request->validated('topic', ''),
    socialNetworks: $request->validated('social_networks'),
    tone: $request->validated('tone'),
    keywords: $request->validated('keywords', []),
    language: $request->validated('language'),
    campaignId: $request->validated('campaign_id'),
    generationMode: $request->validated('generation_mode', 'fields_only'),
));
```

- [ ] **Step 2: Commit**

```bash
git add app/Infrastructure/ContentAI/Controllers/AIController.php
git commit -m "feat(content-ai): pass campaignId and generationMode through controller"
```

---

### Task 16: AI generation with brief feature tests

**Files:**
- Create: `tests/Feature/ContentAI/AIGenerationBriefTest.php`

- [ ] **Step 1: Write feature tests**

```php
<?php
// tests/Feature/ContentAI/AIGenerationBriefTest.php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithAuth;

uses(InteractsWithAuth::class);

beforeEach(function () {
    $this->setUpAuth();
    $this->seed(PlanSeeder::class);

    $this->user = $this->createUserInDb();
    $this->orgData = $this->createOrgWithOwner($this->user['id']);
    $this->orgId = $this->orgData['org']['id'];

    $this->headers = $this->authHeaders($this->user['id'], $this->orgId, $this->user['email']);

    DB::table('subscriptions')->insert([
        'id' => (string) Str::uuid(),
        'organization_id' => $this->orgId,
        'plan_id' => PlanSeeder::PROFESSIONAL_PLAN_ID,
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->startOfMonth()->toDateTimeString(),
        'current_period_end' => now()->endOfMonth()->toDateTimeString(),
        'cancel_at_period_end' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $this->mockGenerator = Mockery::mock(TextGeneratorInterface::class);
    $this->app->instance(TextGeneratorInterface::class, $this->mockGenerator);
});

function createCampaignWithBrief($test, ?string $briefText = 'Campaign for Black Friday'): string
{
    $campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $test->orgId,
        'created_by' => $test->user['id'],
        'name' => 'Test Campaign ' . Str::random(5),
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => $briefText,
        'brief_target_audience' => 'Young adults 18-30',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    return $campaignId;
}

it('generates title with fields_only (default, backward compatible) — 200', function () {
    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => $topic === 'Black Friday promotion for fashion store')
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Generated Title']]],
            tokensInput: 120, tokensOutput: 85, model: 'gpt-4o', durationMs: 1200, costEstimate: 0.003,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'topic' => 'Black Friday promotion for fashion store',
    ]);

    $response->assertOk();
});

it('generates title with brief_only — 200', function () {
    $campaignId = createCampaignWithBrief($this);

    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => str_contains($topic, '[CAMPAIGN BRIEF]') && str_contains($topic, 'Campaign for Black Friday'))
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Brief-based Title']]],
            tokensInput: 150, tokensOutput: 90, model: 'gpt-4o', durationMs: 1300, costEstimate: 0.004,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.attributes.generation_type', 'title');
});

it('generates title with brief_and_fields — 200', function () {
    $campaignId = createCampaignWithBrief($this);

    $this->mockGenerator->shouldReceive('generateTitle')
        ->once()
        ->withArgs(fn ($topic) => str_contains($topic, '[CAMPAIGN BRIEF]') && str_contains($topic, '[USER TOPIC]'))
        ->andReturn(new TextGenerationResult(
            output: ['suggestions' => [['title' => 'Combined Title']]],
            tokensInput: 200, tokensOutput: 100, model: 'gpt-4o', durationMs: 1500, costEstimate: 0.005,
        ));

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_and_fields',
        'campaign_id' => $campaignId,
        'topic' => 'Specific Black Friday deals on jeans',
    ]);

    $response->assertOk();
});

it('brief_only without campaign_id returns 422', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('campaign_id');
});

it('brief_only with campaign without brief returns 422', function () {
    $campaignId = createCampaignWithBrief($this, null);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    $response->assertStatus(422);
});

it('brief_only with other org campaign returns 404/422', function () {
    // Create campaign in a different org
    $otherUser = $this->createUserInDb();
    $otherOrgData = $this->createOrgWithOwner($otherUser['id']);
    $otherOrgId = $otherOrgData['org']['id'];

    $campaignId = (string) Str::uuid();
    DB::table('campaigns')->insert([
        'id' => $campaignId,
        'organization_id' => $otherOrgId,
        'created_by' => $otherUser['id'],
        'name' => 'Other Org Campaign',
        'status' => 'draft',
        'tags' => '[]',
        'brief_text' => 'Other org brief',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $response = $this->withHeaders($this->headers)->postJson('/api/v1/ai/generate-title', [
        'generation_mode' => 'brief_only',
        'campaign_id' => $campaignId,
    ]);

    // Should fail with 404 (campaign not found for this org) or 422
    $response->assertStatus(404);
});
```

- [ ] **Step 2: Run tests**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Feature/ContentAI/AIGenerationBriefTest.php`
Expected: 6 tests PASS

- [ ] **Step 3: Run all ContentAI tests for regression check**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Feature/ContentAI/ tests/Unit/Application/ContentAI/ tests/Unit/Infrastructure/ContentAI/`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/ContentAI/AIGenerationBriefTest.php
git commit -m "test(content-ai): add feature tests for AI generation with campaign brief"
```

---

## Chunk 4: Final Integration + Cleanup

### Task 17: Verify exception handling (no code changes needed)

`CampaignBriefRequiredException` extends `DomainException`, which is already caught by the generic handler in `bootstrap/app.php` (returns 422). `CampaignNotFoundException` has its own specific handler returning 404. Both exceptions work out of the box — no changes required.

- [ ] **Step 1: Verify by running the feature tests from Task 16**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test tests/Feature/ContentAI/AIGenerationBriefTest.php`
Expected: The `brief_only without brief` test returns 422, the `other org campaign` test returns 404.

---

### Task 18: Full regression test suite

- [ ] **Step 1: Run full test suite**

Run: `cd /home/ddrummond/projects/social-media-manager && php artisan test`
Expected: All tests PASS

- [ ] **Step 2: Fix any failures found**

Address any test failures caused by the new `brief` parameter in Campaign entity (existing tests that construct Campaign directly may need updating).

- [ ] **Step 3: Final commit (only modified files)**

Stage only the specific files that were fixed — do NOT use `git add -A` (many unrelated untracked files exist).

```bash
git add <specific-files-that-were-fixed>
git commit -m "fix: address test regressions from Campaign Brief integration"
```

---

### Task 19: Update roadmap

**Files:**
- Modify: `docs/roadmap.md`

- [ ] **Step 1: Add Campaign Brief as Sprint 22**

Add a new sprint entry for Campaign Brief in `docs/roadmap.md` after the existing sprints, marking it as complete.

- [ ] **Step 2: Commit**

```bash
git add docs/roadmap.md
git commit -m "docs: add Campaign Brief (Sprint 22) to roadmap"
```
