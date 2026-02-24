<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Services;

use App\Application\Media\Contracts\MediaStorageInterface;
use Illuminate\Filesystem\FilesystemManager;

final class LaravelMediaStorageService implements MediaStorageInterface
{
    public function __construct(
        private readonly FilesystemManager $filesystemManager,
    ) {}

    public function store(string $disk, string $path, string $contents): void
    {
        $this->filesystemManager->disk($disk)->put($path, $contents);
    }

    public function delete(string $disk, string $path): void
    {
        $this->filesystemManager->disk($disk)->delete($path);
    }

    public function generatePath(string $organizationId, string $fileName): string
    {
        return "orgs/{$organizationId}/media/{$fileName}";
    }
}
