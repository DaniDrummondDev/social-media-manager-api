<?php

declare(strict_types=1);

use App\Application\SocialAccount\DTOs\SocialAccountListOutput;
use App\Application\SocialAccount\UseCases\ListSocialAccountsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

beforeEach(function () {
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);

    $this->useCase = new ListSocialAccountsUseCase($this->socialAccountRepository);
});

it('returns list of accounts for organization', function () {
    $orgId = Uuid::generate();
    $credentials = OAuthCredentials::create(
        accessToken: EncryptedToken::fromEncrypted('token'),
        refreshToken: null,
        expiresAt: null,
        scopes: [],
    );

    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-123',
        username: 'myaccount',
        credentials: $credentials,
    );

    $this->socialAccountRepository->shouldReceive('findByOrganizationId')->once()->andReturn([$account]);

    $output = $this->useCase->execute((string) $orgId);

    expect($output)->toBeInstanceOf(SocialAccountListOutput::class)
        ->and($output->accounts)->toHaveCount(1)
        ->and($output->accounts[0]->username)->toBe('myaccount');
});

it('returns empty list when no accounts exist', function () {
    $this->socialAccountRepository->shouldReceive('findByOrganizationId')->once()->andReturn([]);

    $output = $this->useCase->execute((string) Uuid::generate());

    expect($output->accounts)->toBeEmpty();
});
