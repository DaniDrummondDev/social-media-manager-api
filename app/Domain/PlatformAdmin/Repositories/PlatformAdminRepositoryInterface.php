<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Repositories;

use App\Domain\PlatformAdmin\Entities\PlatformAdmin;
use App\Domain\Shared\ValueObjects\Uuid;

interface PlatformAdminRepositoryInterface
{
    public function findById(Uuid $id): ?PlatformAdmin;

    public function findByUserId(Uuid $userId): ?PlatformAdmin;

    /**
     * @return array<PlatformAdmin>
     */
    public function findAll(): array;

    public function create(PlatformAdmin $admin): void;

    public function update(PlatformAdmin $admin): void;

    public function countActiveSuperAdmins(): int;
}
