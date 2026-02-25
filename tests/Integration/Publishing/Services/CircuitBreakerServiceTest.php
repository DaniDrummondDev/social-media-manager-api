<?php

declare(strict_types=1);

use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Publishing\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = new CircuitBreakerService;

    // Clear circuit breaker keys for all providers
    foreach (SocialProvider::cases() as $provider) {
        Cache::store('redis')->forget("circuit_breaker:{$provider->value}:failures");
        Cache::store('redis')->forget("circuit_breaker:{$provider->value}:open_until");
    }
});

it('starts with circuit closed', function () {
    expect($this->service->isOpen(SocialProvider::Instagram))->toBeFalse();
});

it('stays closed below failure threshold', function () {
    for ($i = 0; $i < 4; $i++) {
        $this->service->recordFailure(SocialProvider::Instagram);
    }

    expect($this->service->isOpen(SocialProvider::Instagram))->toBeFalse();
});

it('opens circuit after reaching failure threshold', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->service->recordFailure(SocialProvider::Instagram);
    }

    expect($this->service->isOpen(SocialProvider::Instagram))->toBeTrue();
});

it('resets circuit on success', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->service->recordFailure(SocialProvider::TikTok);
    }

    expect($this->service->isOpen(SocialProvider::TikTok))->toBeTrue();

    $this->service->recordSuccess(SocialProvider::TikTok);

    expect($this->service->isOpen(SocialProvider::TikTok))->toBeFalse();
});

it('isolates circuit state per provider', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->service->recordFailure(SocialProvider::Instagram);
    }

    expect($this->service->isOpen(SocialProvider::Instagram))->toBeTrue()
        ->and($this->service->isOpen(SocialProvider::TikTok))->toBeFalse()
        ->and($this->service->isOpen(SocialProvider::YouTube))->toBeFalse();
});

it('allows accumulating failures after reset', function () {
    // Trip the circuit
    for ($i = 0; $i < 5; $i++) {
        $this->service->recordFailure(SocialProvider::YouTube);
    }
    expect($this->service->isOpen(SocialProvider::YouTube))->toBeTrue();

    // Reset
    $this->service->recordSuccess(SocialProvider::YouTube);
    expect($this->service->isOpen(SocialProvider::YouTube))->toBeFalse();

    // Failures below threshold should keep it closed
    for ($i = 0; $i < 3; $i++) {
        $this->service->recordFailure(SocialProvider::YouTube);
    }
    expect($this->service->isOpen(SocialProvider::YouTube))->toBeFalse();
});
