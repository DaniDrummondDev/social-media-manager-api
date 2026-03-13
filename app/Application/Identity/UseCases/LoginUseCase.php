<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\DTOs\LoginInput;
use App\Application\Identity\DTOs\TwoFactorChallengeOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;

final class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthTokenServiceInterface $authTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly HashServiceInterface $hashService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(LoginInput $input): AuthTokensOutput|TwoFactorChallengeOutput
    {
        $user = $this->userRepository->findByEmail(Email::fromString($input->email));

        if ($user === null || ! $user->password->verify($input->password)) {
            throw new AuthenticationException;
        }

        if ($user->status !== UserStatus::Active) {
            throw new AuthenticationException('Account is not active');
        }

        if ($user->password->needsRehash()) {
            $rehashed = $user->changePassword(HashedPassword::fromPlainText($input->password));
            $this->userRepository->update($rehashed->releaseEvents());
        }

        if ($user->twoFactorEnabled) {
            $tempToken = $this->authTokenService->generateTempToken((string) $user->id);

            return new TwoFactorChallengeOutput(
                requires2fa: true,
                tempToken: $tempToken['token'],
            );
        }

        return $this->issueTokens($user, $input->ipAddress, $input->userAgent);
    }

    private function issueTokens(
        \App\Domain\Identity\Entities\User $user,
        string $ipAddress,
        string $userAgent,
    ): AuthTokensOutput {
        // Token is issued without org context at login.
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
            expiresAt: new \DateTimeImmutable('+7 days'),
        );

        $loggedIn = $user->recordLogin($ipAddress, $userAgent, $orgId);
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
