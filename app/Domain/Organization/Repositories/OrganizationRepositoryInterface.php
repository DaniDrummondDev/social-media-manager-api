<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repositories;

use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Shared\ValueObjects\Uuid;

interface OrganizationRepositoryInterface
{
    public function create(Organization $organization): void;

    public function update(Organization $organization): void;

    public function findById(Uuid $id): ?Organization;

    public function findBySlug(OrganizationSlug $slug): ?Organization;

    public function delete(Uuid $id): void;

    /** @return Organization[] */
    public function listByUserId(Uuid $userId): array;
}
