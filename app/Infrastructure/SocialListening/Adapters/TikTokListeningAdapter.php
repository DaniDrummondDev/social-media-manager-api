<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Adapters;

use App\Application\SocialListening\Contracts\SocialListeningAdapterInterface;
use App\Domain\SocialListening\ValueObjects\QueryType;
use DateTimeImmutable;

final class TikTokListeningAdapter implements SocialListeningAdapterInterface
{
    /**
     * @return array<array<string, mixed>>
     */
    public function fetchMentions(string $queryValue, QueryType $type, string $platform, DateTimeImmutable $since): array
    {
        // TODO: Implement TikTok listening API integration
        return [];
    }
}
