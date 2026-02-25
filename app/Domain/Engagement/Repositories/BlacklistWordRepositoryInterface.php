<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\BlacklistWord;
use App\Domain\Shared\ValueObjects\Uuid;

interface BlacklistWordRepositoryInterface
{
    public function create(BlacklistWord $word): void;

    public function delete(Uuid $id): void;

    public function findById(Uuid $id): ?BlacklistWord;

    /**
     * @return array<BlacklistWord>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    /**
     * @return array<string>
     */
    public function findAllWords(Uuid $organizationId): array;
}
