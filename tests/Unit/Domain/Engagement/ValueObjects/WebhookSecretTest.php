<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\WebhookSecret;

it('generates with whsec_ prefix', function () {
    $secret = WebhookSecret::generate();

    expect((string) $secret)->toStartWith('whsec_')
        ->and(strlen((string) $secret))->toBe(70); // whsec_ (6) + 64 hex chars
});

it('signs payload with HMAC-SHA256', function () {
    $secret = WebhookSecret::fromString('whsec_test_secret');
    $payload = '{"event":"test"}';
    $timestamp = 1700000000;

    $signature = $secret->sign($payload, $timestamp);

    $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret');
    expect($signature)->toBe($expected);
});

it('creates from string', function () {
    $secret = WebhookSecret::fromString('whsec_abc123');

    expect((string) $secret)->toBe('whsec_abc123')
        ->and($secret->value)->toBe('whsec_abc123');
});

it('generates unique secrets', function () {
    $secret1 = WebhookSecret::generate();
    $secret2 = WebhookSecret::generate();

    expect((string) $secret1)->not->toBe((string) $secret2);
});
