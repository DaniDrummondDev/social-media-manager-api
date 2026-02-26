<?php

declare(strict_types=1);

namespace App\Application\Engagement\Contracts;

interface CrmOAuthStateServiceInterface
{
    public function generateState(string $organizationId, string $userId, string $provider): string;

    /**
     * Validates and consumes a state token (single-use).
     *
     * @return array{organizationId: string, userId: string, provider: string}|null
     */
    public function validateAndConsumeState(string $state): ?array;
}
