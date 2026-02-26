<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Repositories;

use App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface;
use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\ContentAI\ValueObjects\PerformanceScore;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ContentAI\Models\PromptTemplateModel;
use DateTimeImmutable;

final class EloquentPromptTemplateRepository implements PromptTemplateRepositoryInterface
{
    public function __construct(
        private readonly PromptTemplateModel $model,
    ) {}

    public function create(PromptTemplate $template): void
    {
        $this->model->newQuery()->create($this->toArray($template));
    }

    public function update(PromptTemplate $template): void
    {
        $this->model->newQuery()
            ->where('id', (string) $template->id)
            ->update($this->toArray($template));
    }

    public function findById(Uuid $id): ?PromptTemplate
    {
        /** @var PromptTemplateModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findBestPerformer(?Uuid $organizationId, string $generationType): ?PromptTemplate
    {
        $query = $this->model->newQuery()
            ->where('generation_type', $generationType)
            ->where('is_active', true)
            ->where('total_uses', '>=', 20)
            ->whereNotNull('performance_score')
            ->orderByDesc('performance_score');

        if ($organizationId !== null) {
            $query->where('organization_id', (string) $organizationId);
        } else {
            $query->whereNull('organization_id');
        }

        /** @var PromptTemplateModel|null $record */
        $record = $query->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function findDefault(?Uuid $organizationId, string $generationType): ?PromptTemplate
    {
        // Try org-level default first
        if ($organizationId !== null) {
            /** @var PromptTemplateModel|null $record */
            $record = $this->model->newQuery()
                ->where('organization_id', (string) $organizationId)
                ->where('generation_type', $generationType)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            if ($record) {
                return $this->toDomain($record);
            }
        }

        // Fall back to system template
        /** @var PromptTemplateModel|null $record */
        $record = $this->model->newQuery()
            ->whereNull('organization_id')
            ->where('generation_type', $generationType)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<PromptTemplate>
     */
    public function findActiveByOrganizationAndType(?Uuid $organizationId, string $generationType): array
    {
        $query = $this->model->newQuery()
            ->where('generation_type', $generationType)
            ->where('is_active', true);

        if ($organizationId !== null) {
            $query->where('organization_id', (string) $organizationId);
        } else {
            $query->whereNull('organization_id');
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, PromptTemplateModel> $records */
        $records = $query->orderByDesc('performance_score')->get();

        return $records->map(fn (PromptTemplateModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<PromptTemplate>
     */
    public function findAllActive(?Uuid $organizationId = null, ?string $generationType = null): array
    {
        $query = $this->model->newQuery()->where('is_active', true);

        if ($organizationId !== null) {
            $query->where(function ($q) use ($organizationId) {
                $q->where('organization_id', (string) $organizationId)
                    ->orWhereNull('organization_id');
            });
        }

        if ($generationType !== null) {
            $query->where('generation_type', $generationType);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, PromptTemplateModel> $records */
        $records = $query->orderByDesc('performance_score')->get();

        return $records->map(fn (PromptTemplateModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(PromptTemplate $template): array
    {
        return [
            'id' => (string) $template->id,
            'organization_id' => $template->organizationId !== null ? (string) $template->organizationId : null,
            'generation_type' => $template->generationType,
            'version' => $template->version,
            'name' => $template->name,
            'system_prompt' => $template->systemPrompt,
            'user_prompt_template' => $template->userPromptTemplate,
            'variables' => $template->variables,
            'is_active' => $template->isActive,
            'is_default' => $template->isDefault,
            'performance_score' => $template->performanceScore?->value,
            'total_uses' => $template->totalUses,
            'total_accepted' => $template->totalAccepted,
            'total_edited' => $template->totalEdited,
            'total_rejected' => $template->totalRejected,
            'created_by' => $template->createdBy !== null ? (string) $template->createdBy : null,
            'created_at' => $template->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $template->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(PromptTemplateModel $model): PromptTemplate
    {
        $organizationId = $model->getAttribute('organization_id');
        $performanceScore = $model->getAttribute('performance_score');
        $createdBy = $model->getAttribute('created_by');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        $variables = $model->getAttribute('variables');
        $variablesArray = is_array($variables) ? $variables : json_decode((string) $variables, true);

        return PromptTemplate::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: $organizationId !== null ? Uuid::fromString($organizationId) : null,
            generationType: $model->getAttribute('generation_type'),
            version: $model->getAttribute('version'),
            name: $model->getAttribute('name'),
            systemPrompt: $model->getAttribute('system_prompt'),
            userPromptTemplate: $model->getAttribute('user_prompt_template'),
            variables: $variablesArray,
            isActive: (bool) $model->getAttribute('is_active'),
            isDefault: (bool) $model->getAttribute('is_default'),
            performanceScore: $performanceScore !== null ? PerformanceScore::fromFloat((float) $performanceScore) : null,
            totalUses: (int) $model->getAttribute('total_uses'),
            totalAccepted: (int) $model->getAttribute('total_accepted'),
            totalEdited: (int) $model->getAttribute('total_edited'),
            totalRejected: (int) $model->getAttribute('total_rejected'),
            createdBy: $createdBy !== null ? Uuid::fromString($createdBy) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
