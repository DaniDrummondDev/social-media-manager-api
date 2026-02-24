<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Application\Identity\DTOs\AuthTokensOutput;
use App\Application\Identity\Exceptions\AuthenticationException;
use App\Application\Organization\DTOs\SwitchOrganizationInput;
use App\Application\Organization\Exceptions\AuthorizationException;
use App\Application\Shared\Contracts\HashServiceInterface;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class SwitchOrganizationUseCase
{
    public function __construct(
        private readonly OrganizationMemberRepositoryInterface $memberRepository,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthTokenServiceInterface $authTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly HashServiceInterface $hashService,
    ) {}

    public function execute(SwitchOrganizationInput $input): AuthTokensOutput
    {
        $userId = Uuid::fromString($input->userId);
        $orgId = Uuid::fromString($input->organizationId);

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new AuthenticationException('User not found');
        }

        $organization = $this->organizationRepository->findById($orgId);

        if ($organization === null || ! $organization->isActive()) {
            throw new AuthorizationException('Organization not found or inactive');
        }

        $member = $this->memberRepository->findByOrgAndUser($orgId, $userId);

        if ($member === null) {
            throw new AuthorizationException('Not a member of this organization');
        }

        $accessToken = $this->authTokenService->generateAccessToken(
            userId: (string) $user->id,
            organizationId: (string) $orgId,
            email: (string) $user->email,
            role: $member->role->value,
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

        return new AuthTokensOutput(
            accessToken: $accessToken['token'],
            refreshToken: $refreshTokenValue,
            tokenType: 'Bearer',
            expiresIn: $accessToken['expires_in'],
        );
    }
}
