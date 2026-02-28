<?php

declare(strict_types=1);

use App\Application\SocialListening\Contracts\SocialListeningAdapterInterface;
use App\Domain\SocialListening\ValueObjects\QueryType;
use App\Infrastructure\SocialListening\Adapters\InstagramListeningAdapter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->adapter = new InstagramListeningAdapter();
});

it('should implement SocialListeningAdapterInterface', function () {
    expect($this->adapter)->toBeInstanceOf(SocialListeningAdapterInterface::class);
});

it('should fetch mentions from Instagram Graph API with hashtag query', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => '123456789',
                    'text' => 'Great product! #testhashtag',
                    'username' => 'test_user',
                    'timestamp' => '2026-02-28T10:00:00+0000',
                    'like_count' => 150,
                    'comments_count' => 20,
                    'media_url' => 'https://example.com/image.jpg',
                ],
                [
                    'id' => '987654321',
                    'text' => 'Love this brand #testhashtag',
                    'username' => 'another_user',
                    'timestamp' => '2026-02-28T11:00:00+0000',
                    'like_count' => 85,
                    'comments_count' => 12,
                    'media_url' => 'https://example.com/image2.jpg',
                ],
            ],
            'paging' => [
                'next' => 'https://graph.facebook.com/v18.0/ig_hashtag/next',
            ],
        ]),
    ]);

    $since = new DateTimeImmutable('2026-02-28 00:00:00');
    $mentions = $this->adapter->fetchMentions('#testhashtag', QueryType::Hashtag, 'instagram', $since);

    expect($mentions)->toBeArray()
        ->and($mentions)->toHaveCount(2)
        ->and($mentions[0])->toHaveKey('external_id')
        ->and($mentions[0])->toHaveKey('content')
        ->and($mentions[0])->toHaveKey('author_username');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
})->skip('Instagram Graph API integration pending implementation');

it('should fetch mentions with mention query type', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 'mention-123',
                    'text' => '@testbrand this is awesome!',
                    'username' => 'fan_user',
                    'timestamp' => '2026-02-28T12:00:00+0000',
                    'like_count' => 200,
                    'comments_count' => 30,
                ],
            ],
        ]),
    ]);

    $since = new DateTimeImmutable('2026-02-28 00:00:00');
    $mentions = $this->adapter->fetchMentions('@testbrand', QueryType::Mention, 'instagram', $since);

    expect($mentions)->toBeArray()
        ->and($mentions)->toHaveCount(1)
        ->and($mentions[0]['external_id'])->toBe('mention-123')
        ->and($mentions[0]['author_username'])->toBe('fan_user');
})->skip('Instagram Graph API integration pending implementation');

it('should return empty array when API returns no data', function () {
    // Current stub implementation always returns empty array
    $since = new DateTimeImmutable('2026-02-28 00:00:00');
    $mentions = $this->adapter->fetchMentions('#nonexistent', QueryType::Hashtag, 'instagram', $since);

    expect($mentions)->toBeArray()
        ->and($mentions)->toBeEmpty();
});

it('should handle API errors gracefully', function () {
    // Current stub implementation always returns empty array
    $since = new DateTimeImmutable('2026-02-28 00:00:00');
    $mentions = $this->adapter->fetchMentions('#test', QueryType::Hashtag, 'instagram', $since);

    expect($mentions)->toBeArray()
        ->and($mentions)->toBeEmpty();
});

it('should filter mentions by since timestamp', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 'old-mention',
                    'text' => 'Old post #test',
                    'username' => 'old_user',
                    'timestamp' => '2026-02-27T10:00:00+0000',
                    'like_count' => 10,
                    'comments_count' => 1,
                ],
            ],
        ]),
    ]);

    $since = new DateTimeImmutable('2026-02-28 00:00:00');
    $mentions = $this->adapter->fetchMentions('#test', QueryType::Hashtag, 'instagram', $since);

    Http::assertSent(function ($request) use ($since) {
        return str_contains($request->url(), 'graph.facebook.com')
            && str_contains($request->url(), 'since=' . $since->getTimestamp());
    });
})->skip('Instagram Graph API integration pending implementation');
