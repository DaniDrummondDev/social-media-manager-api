<?php

declare(strict_types=1);

use App\Application\SocialAccount\Contracts\OAuthStateServiceInterface;

beforeEach(function () {
    $this->service = app(OAuthStateServiceInterface::class);
});

it('generates and validates state roundtrip', function () {
    $state = $this->service->generateState('org-123', 'user-456', 'instagram');

    expect($state)->toBeString()
        ->and(strlen($state))->toBe(64); // 32 bytes hex

    $payload = $this->service->validateAndConsumeState($state);

    expect($payload)->not->toBeNull()
        ->and($payload['organizationId'])->toBe('org-123')
        ->and($payload['userId'])->toBe('user-456')
        ->and($payload['provider'])->toBe('instagram');
});

it('consumes state only once', function () {
    $state = $this->service->generateState('org-1', 'user-1', 'tiktok');

    $first = $this->service->validateAndConsumeState($state);
    $second = $this->service->validateAndConsumeState($state);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();
});

it('returns null for unknown state', function () {
    $result = $this->service->validateAndConsumeState('nonexistent-state-token');

    expect($result)->toBeNull();
});

it('stores correct payload fields', function () {
    $state = $this->service->generateState('org-abc', 'user-xyz', 'youtube');

    $payload = $this->service->validateAndConsumeState($state);

    expect($payload)->toHaveKeys(['organizationId', 'userId', 'provider']);
});
