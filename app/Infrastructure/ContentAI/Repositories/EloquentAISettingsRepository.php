<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Repositories;

use App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface;
use App\Domain\ContentAI\Entities\AISettings;
use App\Domain\ContentAI\ValueObjects\Language;
use App\Domain\ContentAI\ValueObjects\Tone;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ContentAI\Models\AISettingsModel;
use DateTimeImmutable;

final class EloquentAISettingsRepository implements AISettingsRepositoryInterface
{
    public function __construct(
        private readonly AISettingsModel $model,
    ) {}

    public function findByOrganizationId(Uuid $organizationId): ?AISettings
    {
        /** @var AISettingsModel|null $record */
        $record = $this->model->newQuery()->find((string) $organizationId);

        return $record ? $this->toDomain($record) : null;
    }

    public function upsert(AISettings $settings): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['organization_id' => (string) $settings->organizationId],
            $this->toArray($settings),
        );
    }

    private function toDomain(AISettingsModel $model): AISettings
    {
        return AISettings::reconstitute(
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            defaultTone: Tone::from($model->getAttribute('default_tone')),
            customToneDescription: $model->getAttribute('custom_tone_description'),
            defaultLanguage: Language::from($model->getAttribute('default_language')),
            monthlyGenerationLimit: (int) $model->getAttribute('monthly_generation_limit'),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AISettings $settings): array
    {
        return [
            'organization_id' => (string) $settings->organizationId,
            'default_tone' => $settings->defaultTone->value,
            'custom_tone_description' => $settings->customToneDescription,
            'default_language' => $settings->defaultLanguage->value,
            'monthly_generation_limit' => $settings->monthlyGenerationLimit,
        ];
    }
}
