<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Contracts;

use App\Domain\SocialListening\ValueObjects\QueryType;
use DateTimeImmutable;

interface SocialListeningAdapterInterface
{
    /**
     * Fetch mentions from a social platform.
     *
     * @return array<array<string, mixed>> Array of raw mention data
     */
    public function fetchMentions(string $queryValue, QueryType $type, string $platform, DateTimeImmutable $since): array;
}
