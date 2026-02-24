<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\Contracts\TwoFactorServiceInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\Verify2FAInput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Exceptions\InvalidTwoFactorCodeException;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class Verify2FALoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthTokenServiceInterface $authTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly TwoFactorServiceInterface $twoFactorService,
        private readonly HashServiceInterface $hashService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(Verify2FAInput $input): AuthTokensOutput
    {
        $userId = $this->authTokenService->validateTempToken($input->tempToken);

        if ($userId === null) {
            throw new AuthenticationException('Invalid or expired temporary token');
        }

        $user = $this->userRepository->findById(Uuid::fromString($userId));

        if ($user === null || ! $user->twoFactorEnabled || $user->twoFactorSecret === null) {
            throw new AuthenticationException;
        }

        $decryptedSecret = $this->twoFactorService->decryptSecret($user->twoFactorSecret->encryptedValue);

        if (! $this->twoFactorService->verifyCode($decryptedSecret, $input->otpCode)) {
            throw new InvalidTwoFactorCodeException;
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

        $refreshTokenValue = $this->authTokenService->generateRefreshToken();
        $familyId = (string) Uuid::generate();

        $this->refreshTokenRepository->store(
            id: (string) Uuid::generate(),
            userId: (string) $user->id,
            tokenHash: $this->hashService->hash($refreshTokenValue),
            familyId: $familyId,
            expiresAt: new DateTimeImmutable('+7 days'),
        );

        $loggedIn = $user->recordLogin($input->ipAddress, $input->userAgent, $orgId);
        $this->userRepository->update($loggedIn);
        $this->eventDispatcher->dispatch(...$loggedIn->domainEvents);

        return new AuthTokensOutput(
            accessToken: $accessToken['token'],
            refreshToken: $refreshTokenValue,
            tokenType: 'Bearer',
            expiresIn: $accessToken['expires_in'],
        );
    }
}
