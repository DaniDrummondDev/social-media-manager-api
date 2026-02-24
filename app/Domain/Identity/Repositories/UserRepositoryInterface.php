<?php

declare(strict_types=1);

namespace App\Domain\Identity\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Shared\ValueObjects\Uuid;

interface UserRepositoryInterface
{
    public function create(User $user): void;

    public function update(User $user): void;

    public function findById(Uuid $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function delete(Uuid $id): void;

    public function existsByEmail(Email $email): bool;
}
