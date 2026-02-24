<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Repositories;

use App\Domain\Campaign\Contracts\ContentMediaRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Campaign\Models\ContentMediaModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentContentMediaRepository implements ContentMediaRepositoryInterface
{
    public function __construct(
        private readonly ContentMediaModel $model,
    ) {}

    /**
     * @param  array<int, string>  $mediaIds
     */
    public function sync(Uuid $contentId, array $mediaIds): void
    {
        DB::transaction(function () use ($contentId, $mediaIds): void {
            $this->model->newQuery()
                ->where('content_id', (string) $contentId)
                ->delete();

            if ($mediaIds === []) {
                return;
            }

            $now = new DateTimeImmutable;
            $rows = [];

            foreach ($mediaIds as $position => $mediaId) {
                $rows[] = [
                    'id' => (string) Uuid::generate(),
                    'content_id' => (string) $contentId,
                    'media_id' => $mediaId,
                    'position' => $position,
                    'created_at' => $now->format('Y-m-d H:i:s'),
                ];
            }

            $this->model->newQuery()->insert($rows);
        });
    }

    /**
     * @return array<int, array{media_id: string, position: int}>
     */
    public function findByContentId(Uuid $contentId): array
    {
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->orderBy('position')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ContentMediaModel> $records */
        return $records->map(fn (ContentMediaModel $record) => [
            'media_id' => $record->getAttribute('media_id'),
            'position' => (int) $record->getAttribute('position'),
        ])->all();
    }
}
