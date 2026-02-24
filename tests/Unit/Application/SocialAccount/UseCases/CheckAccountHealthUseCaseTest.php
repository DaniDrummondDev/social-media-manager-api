<?php

declare(strict_types=1);

use App\Application\SocialAccount\DTOs\AccountHealthOutput;
use App\Application\SocialAccount\DTOs\CheckAccountHealthInput;
use App\Application\SocialAccount\Exceptions\SocialAccountNotFoundException;
use App\Application\SocialAccount\UseCases\CheckAccountHealthUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

beforeEach(function () {
    $this->socialAccountRepository = Mockery::mock(SocialAccountRepositoryInterface::class);

    $this->useCase = new CheckAccountHealthUseCase($this->socialAccountRepository);
});

it('reports healthy account', function () {
    $orgId = Uuid::generate();
    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::Instagram,
        providerUserId: 'ig-123',
        username: 'myaccount',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('token'),
            null,
            new DateTimeImmutable('+2 hours'),
            [],
        ),
    );

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $output = $this->useCase->execute(new CheckAccountHealthInput(
        organizationId: (string) $orgId,
        accountId: (string) $account->id,
    ));

    expect($output)->toBeInstanceOf(AccountHealthOutput::class)
        ->and($output->canPublish)->toBeTrue()
        ->and($output->isExpired)->toBeFalse()
        ->and($output->willExpireSoon)->toBeFalse();
});

it('reports expired token', function () {
    $orgId = Uuid::generate();
    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::TikTok,
        providerUserId: 'tt-456',
        username: 'ttuser',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('token'),
            null,
            new DateTimeImmutable('-1 hour'), // expired
            [],
        ),
    );

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $output = $this->useCase->execute(new CheckAccountHealthInput(
        organizationId: (string) $orgId,
        accountId: (string) $account->id,
    ));

    expect($output->isExpired)->toBeTrue();
});

it('reports token expiring soon', function () {
    $orgId = Uuid::generate();
    $account = SocialAccount::create(
        organizationId: $orgId,
        connectedBy: Uuid::generate(),
        provider: SocialProvider::YouTube,
        providerUserId: 'yt-789',
        username: 'ytuser',
        credentials: OAuthCredentials::create(
            EncryptedToken::fromEncrypted('token'),
            null,
            new DateTimeImmutable('+30 minutes'), // expires in 30 min (< 60 min default)
            [],
        ),
    );

    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturn($account);

    $output = $this->useCase->execute(new CheckAccountHealthInput(
        organizationId: (string) $orgId,
        accountId: (string) $account->id,
    ));

    expect($output->willExpireSoon)->toBeTrue()
        ->and($output->isExpired)->toBeFalse();
});

it('throws when account not found', function () {
    $this->socialAccountRepository->shouldReceive('findById')->once()->andReturnNull();

    $this->useCase->execute(new CheckAccountHealthInput(
        organizationId: (string) Uuid::generate(),
        accountId: (string) Uuid::generate(),
    ));
})->throws(SocialAccountNotFoundException::class);
