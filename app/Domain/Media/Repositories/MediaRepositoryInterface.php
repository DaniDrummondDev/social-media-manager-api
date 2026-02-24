<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Entities\Media;
use App\Domain\Shared\ValueObjects\Uuid;

interface MediaRepositoryInterface
{
    public function create(Media $media): void;

    public function update(Media $media): void;

    public function findById(Uuid $id): ?Media;

    /** @return Media[] */
    public function findByOrganizationId(Uuid $organizationId): array;

    public function findByChecksum(Uuid $organizationId, string $checksum): ?Media;

    public function delete(Uuid $id): void;

    /** @return Media[] */
    public function findPurgeable(): array;
}
