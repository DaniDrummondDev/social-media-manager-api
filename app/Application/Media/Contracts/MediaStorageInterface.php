<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

interface MediaStorageInterface
{
    public function store(string $disk, string $path, string $contents): void;

    public function delete(string $disk, string $path): void;

    public function generatePath(string $organizationId, string $fileName): string;
}
