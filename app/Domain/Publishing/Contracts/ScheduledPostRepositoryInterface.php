<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Contracts;

use App\Domain\Publishing\Entities\ScheduledPost;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface ScheduledPostRepositoryInterface
{
    public function create(ScheduledPost $post): void;

    public function update(ScheduledPost $post): void;

    public function findById(Uuid $id): ?ScheduledPost;

    /**
     * @return ScheduledPost[]
     */
    public function findByOrganizationId(
        Uuid $organizationId,
        ?string $status = null,
        ?string $provider = null,
        ?string $campaignId = null,
        ?DateTimeImmutable $from = null,
        ?DateTimeImmutable $to = null,
    ): array;

    /**
     * @return ScheduledPost[]
     */
    public function findDuePosts(DateTimeImmutable $now): array;

    /**
     * @return ScheduledPost[]
     */
    public function findRetryable(DateTimeImmutable $now): array;

    /**
     * @return ScheduledPost[]
     */
    public function findByContentId(Uuid $contentId): array;

    public function existsByContentAndAccount(Uuid $contentId, Uuid $socialAccountId): bool;
}
