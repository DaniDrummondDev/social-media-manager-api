<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Shared\Contracts\HashServiceInterface;

final class LogoutUseCase
{
    public function __construct(
        private readonly AuthTokenServiceInterface $authTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly HashServiceInterface $hashService,
    ) {}

    public function execute(
        string $jti,
        int $tokenTtlSeconds,
        ?string $refreshToken = null,
        bool $allSessions = false,
        ?string $userId = null,
    ): void {
        $this->authTokenService->blacklistToken($jti, $tokenTtlSeconds);

        if ($refreshToken !== null) {
            $tokenHash = $this->hashService->hash($refreshToken);
            $record = $this->refreshTokenRepository->findByTokenHash($tokenHash);

            if ($record !== null) {
                $this->refreshTokenRepository->revokeById($record['id']);
            }
        }

        if ($allSessions && $userId !== null) {
            $this->refreshTokenRepository->revokeAllForUser($userId);
        }
    }
}
