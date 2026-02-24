<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\RefreshTokenInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class RefreshTokenUseCase
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly AuthTokenServiceInterface $authTokenService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly HashServiceInterface $hashService,
    ) {}

    public function execute(RefreshTokenInput $input): AuthTokensOutput
    {
        $tokenHash = $this->hashService->hash($input->refreshToken);
        $record = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($record === null) {
            throw new AuthenticationException('Invalid refresh token');
        }

        if ($record['revoked_at'] !== null) {
            $this->refreshTokenRepository->revokeByFamily($record['family_id']);
            throw new AuthenticationException('Token reuse detected');
        }

        $expiresAt = new DateTimeImmutable($record['expires_at']);
        if (new DateTimeImmutable > $expiresAt) {
            throw new AuthenticationException('Refresh token expired');
        }

        $this->refreshTokenRepository->revokeById($record['id']);

        $user = $this->userRepository->findById(Uuid::fromString($record['user_id']));

        if ($user === null) {
            throw new AuthenticationException;
        }

        // Token is issued without org context.
        // User must call SwitchOrganization to get an org-scoped token.
        $orgId = '';
        $role = '';

        $accessToken = $this->authTokenService->generateAccessToken(
            userId: (string) $user->id,
            organizationId: $orgId,
            email: (string) $user->email,
            role: $role,
        );

        $newRefreshTokenValue = $this->authTokenService->generateRefreshToken();

        $this->refreshTokenRepository->store(
            id: (string) Uuid::generate(),
            userId: $record['user_id'],
            tokenHash: $this->hashService->hash($newRefreshTokenValue),
            familyId: $record['family_id'],
            expiresAt: new DateTimeImmutable('+7 days'),
        );

        return new AuthTokensOutput(
            accessToken: $accessToken['token'],
            refreshToken: $newRefreshTokenValue,
            tokenType: 'Bearer',
            expiresIn: $accessToken['expires_in'],
        );
    }
}
