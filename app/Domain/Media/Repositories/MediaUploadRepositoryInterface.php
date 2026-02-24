<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Entities\MediaUpload;
use App\Domain\Shared\ValueObjects\Uuid;

interface MediaUploadRepositoryInterface
{
    public function create(MediaUpload $upload): void;

    public function update(MediaUpload $upload): void;

    public function findById(Uuid $id): ?MediaUpload;

    /** @return MediaUpload[] */
    public function findExpired(): array;

    public function delete(Uuid $id): void;
}
