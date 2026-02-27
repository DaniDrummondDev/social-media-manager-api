<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Contracts;

interface AdOAuthStateServiceInterface
{
    public function generateState(string $organizationId, string $userId, string $provider): string;

    /**
     * Validates and consumes a state token (single-use).
     *
     * @return array{organizationId: string, userId: string, provider: string}|null
     */
    public function validateAndConsumeState(string $state): ?array;
}
