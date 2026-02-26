<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\ContentProfile;
use App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;
use App\Domain\AIIntelligence\ValueObjects\ProfileStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\AIIntelligence\Models\ContentProfileModel;
use DateTimeImmutable;

final class EloquentContentProfileRepository implements ContentProfileRepositoryInterface
{
    public function __construct(
        private readonly ContentProfileModel $model,
    ) {}

    public function create(ContentProfile $profile): void
    {
        $this->model->newQuery()->create($this->toArray($profile));
    }

    public function update(ContentProfile $profile): void
    {
        $this->model->newQuery()
            ->where('id', (string) $profile->id)
            ->update($this->toArray($profile));
    }

    public function findById(Uuid $id): ?ContentProfile
    {
        /** @var ContentProfileModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByOrganization(
        Uuid $organizationId,
        ?string $provider = null,
        ?Uuid $socialAccountId = null,
    ): ?ContentProfile {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($provider !== null) {
            $query->where('provider', $provider);
        }

        if ($socialAccountId !== null) {
            $query->where('social_account_id', (string) $socialAccountId);
        }

        /** @var ContentProfileModel|null $record */
        $record = $query->orderByDesc('generated_at')->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ContentProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'organization_id' => (string) $profile->organizationId,
            'social_account_id' => $profile->socialAccountId ? (string) $profile->socialAccountId : null,
            'provider' => $profile->provider,
            'status' => $profile->status->value,
            'total_contents_analyzed' => $profile->totalContentsAnalyzed,
            'top_themes' => $profile->topThemes,
            'engagement_patterns' => $profile->engagementPatterns?->toArray() ?? [],
            'content_fingerprint' => $profile->contentFingerprint?->toArray() ?? [],
            'high_performer_traits' => $profile->highPerformerTraits,
            'centroid_embedding' => $profile->centroidEmbedding !== null
                ? json_encode($profile->centroidEmbedding)
                : null,
            'generated_at' => $profile->generatedAt->format('Y-m-d H:i:s'),
            'expires_at' => $profile->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $profile->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $profile->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ContentProfileModel $model): ContentProfile
    {
        $engagementPatterns = $model->getAttribute('engagement_patterns');
        $contentFingerprint = $model->getAttribute('content_fingerprint');
        $centroidEmbedding = $model->getAttribute('centroid_embedding');
        $socialAccountId = $model->getAttribute('social_account_id');
        $generatedAt = $model->getAttribute('generated_at');
        $expiresAt = $model->getAttribute('expires_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        $engagementArray = is_array($engagementPatterns)
            ? $engagementPatterns
            : json_decode((string) $engagementPatterns, true);

        $fingerprintArray = is_array($contentFingerprint)
            ? $contentFingerprint
            : json_decode((string) $contentFingerprint, true);

        $topThemes = $model->getAttribute('top_themes');
        $topThemesArray = is_array($topThemes) ? $topThemes : json_decode((string) $topThemes, true);

        $highPerformerTraits = $model->getAttribute('high_performer_traits');
        $highPerformerArray = is_array($highPerformerTraits)
            ? $highPerformerTraits
            : json_decode((string) $highPerformerTraits, true);

        $centroid = null;
        if ($centroidEmbedding !== null) {
            $centroid = is_array($centroidEmbedding)
                ? $centroidEmbedding
                : json_decode((string) $centroidEmbedding, true);
        }

        return ContentProfile::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            socialAccountId: $socialAccountId ? Uuid::fromString($socialAccountId) : null,
            provider: $model->getAttribute('provider'),
            totalContentsAnalyzed: (int) $model->getAttribute('total_contents_analyzed'),
            topThemes: $topThemesArray ?? [],
            engagementPatterns: ! empty($engagementArray) && isset($engagementArray['avg_likes'])
                ? EngagementPattern::fromArray($engagementArray)
                : null,
            contentFingerprint: ! empty($fingerprintArray) && isset($fingerprintArray['avg_length'])
                ? ContentFingerprint::fromArray($fingerprintArray)
                : null,
            highPerformerTraits: $highPerformerArray ?? [],
            centroidEmbedding: $centroid,
            status: ProfileStatus::from($model->getAttribute('status')),
            generatedAt: new DateTimeImmutable($generatedAt->format('Y-m-d H:i:s')),
            expiresAt: new DateTimeImmutable($expiresAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
