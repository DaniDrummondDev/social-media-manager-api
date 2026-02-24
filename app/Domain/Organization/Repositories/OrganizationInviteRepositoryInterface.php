<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repositories;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Organization\Entities\OrganizationInvite;
use App\Domain\Shared\ValueObjects\Uuid;

interface OrganizationInviteRepositoryInterface
{
    public function create(OrganizationInvite $invite): void;

    public function update(OrganizationInvite $invite): void;

    public function findByToken(string $token): ?OrganizationInvite;

    public function findPendingByOrgAndEmail(Uuid $organizationId, Email $email): ?OrganizationInvite;

    public function delete(Uuid $id): void;

    public function deleteExpired(): int;
}
