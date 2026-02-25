<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\BlacklistWordModel;
use DateTimeImmutable;

final class EloquentBlacklistWordRepository implements BlacklistWordRepositoryInterface
{
    public function __construct(
        private readonly BlacklistWordModel $model,
    ) {}

    public function create(BlacklistWord $word): void
    {
        $this->model->newQuery()->create([
            'id' => (string) $word->id,
            'organization_id' => (string) $word->organizationId,
            'word' => $word->word,
            'is_regex' => $word->isRegex,
            'created_at' => $word->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    public function findById(Uuid $id): ?BlacklistWord
    {
        /** @var BlacklistWordModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<BlacklistWord>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, BlacklistWordModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderBy('word')
            ->get();

        return $records->map(fn (BlacklistWordModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string>
     */
    public function findAllWords(Uuid $organizationId): array
    {
        return $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->pluck('word')
            ->all();
    }

    private function toDomain(BlacklistWordModel $model): BlacklistWord
    {
        $createdAt = $model->getAttribute('created_at');

        return BlacklistWord::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            word: $model->getAttribute('word'),
            isRegex: (bool) $model->getAttribute('is_regex'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
