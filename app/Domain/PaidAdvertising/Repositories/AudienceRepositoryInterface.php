<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\Shared\ValueObjects\Uuid;

interface AudienceRepositoryInterface
{
    public function create(Audience $audience): void;

    public function update(Audience $audience): void;

    public function findById(Uuid $id): ?Audience;

    /**
     * @return array<Audience>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    public function delete(Uuid $id): void;

    public function existsByNameAndOrganization(string $name, Uuid $organizationId): bool;
}
