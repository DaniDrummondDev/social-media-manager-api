<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\OrgStyleProfile;
use App\Domain\AIIntelligence\Repositories\OrgStyleProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\StylePreferences;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\OrgStyleProfileModel;
use DateTimeImmutable;

final class EloquentOrgStyleProfileRepository implements OrgStyleProfileRepositoryInterface
{
    public function __construct(
        private readonly OrgStyleProfileModel $model,
    ) {}

    public function save(OrgStyleProfile $profile): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['id' => (string) $profile->id],
            $this->toArray($profile),
        );
    }

    public function findById(Uuid $id): ?OrgStyleProfile
    {
        /** @var OrgStyleProfileModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findActiveByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
    ): ?OrgStyleProfile {
        /** @var OrgStyleProfileModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('generation_type', $generationType)
            ->where('expires_at', '>', now())
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<OrgStyleProfile>
     */
    public function findActiveByOrganization(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, OrgStyleProfileModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('expires_at', '>', now())
            ->get();

        return $records->map(fn (OrgStyleProfileModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<OrgStyleProfile>
     */
    public function findExpired(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, OrgStyleProfileModel> $records */
        $records = $this->model->newQuery()
            ->where('expires_at', '<=', now())
            ->get();

        return $records->map(fn (OrgStyleProfileModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(OrgStyleProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'organization_id' => (string) $profile->organizationId,
            'generation_type' => $profile->generationType,
            'sample_size' => $profile->sampleSize,
            'tone_preferences' => $profile->stylePreferences->tonePreferences,
            'length_preferences' => $profile->stylePreferences->lengthPreferences,
            'vocabulary_preferences' => $profile->stylePreferences->vocabularyPreferences,
            'structure_preferences' => $profile->stylePreferences->structurePreferences,
            'hashtag_preferences' => $profile->stylePreferences->hashtagPreferences,
            'style_summary' => $profile->styleSummary,
            'confidence_level' => $profile->confidenceLevel->value,
            'generated_at' => $profile->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $profile->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $profile->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $profile->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(OrgStyleProfileModel $model): OrgStyleProfile
    {
        $generatedAt = $model->getAttribute('generated_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return OrgStyleProfile::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            generationType: $model->getAttribute('generation_type'),
            sampleSize: (int) $model->getAttribute('sample_size'),
            stylePreferences: StylePreferences::fromArray([
                'tone_preferences' => is_array($model->getAttribute('tone_preferences'))
                    ? $model->getAttribute('tone_preferences')
                    : json_decode((string) $model->getAttribute('tone_preferences'), true),
                'length_preferences' => is_array($model->getAttribute('length_preferences'))
                    ? $model->getAttribute('length_preferences')
                    : json_decode((string) $model->getAttribute('length_preferences'), true),
                'vocabulary_preferences' => is_array($model->getAttribute('vocabulary_preferences'))
                    ? $model->getAttribute('vocabulary_preferences')
                    : json_decode((string) $model->getAttribute('vocabulary_preferences'), true),
                'structure_preferences' => is_array($model->getAttribute('structure_preferences'))
                    ? $model->getAttribute('structure_preferences')
                    : json_decode((string) $model->getAttribute('structure_preferences'), true),
                'hashtag_preferences' => is_array($model->getAttribute('hashtag_preferences'))
                    ? $model->getAttribute('hashtag_preferences')
                    : json_decode((string) $model->getAttribute('hashtag_preferences'), true),
            ]),
            styleSummary: $model->getAttribute('style_summary'),
            confidenceLevel: ConfidenceLevel::from($model->getAttribute('confidence_level')),
            generatedAt: new DateTimeImmutable($generatedAt->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($expiresAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
