<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

final readonly class MentionSource
{
    private function __construct(
        public string $platform,
        public string $authorUsername,
        public string $authorDisplayName,
        public ?int $authorFollowerCount,
        public ?string $profileUrl,
    ) {}

    public static function create(
        string $platform,
        string $authorUsername,
        string $authorDisplayName,
        ?int $authorFollowerCount = null,
        ?string $profileUrl = null,
    ): self {
        return new self($platform, $authorUsername, $authorDisplayName, $authorFollowerCount, $profileUrl);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            platform: $data['platform'],
            authorUsername: $data['author_username'],
            authorDisplayName: $data['author_display_name'],
            authorFollowerCount: isset($data['author_follower_count']) ? (int) $data['author_follower_count'] : null,
            profileUrl: $data['profile_url'] ?? null,
        );
    }

    public function isInfluencer(int $followerThreshold): bool
    {
        return $this->authorFollowerCount !== null && $this->authorFollowerCount >= $followerThreshold;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'author_username' => $this->authorUsername,
            'author_display_name' => $this->authorDisplayName,
            'author_follower_count' => $this->authorFollowerCount,
            'profile_url' => $this->profileUrl,
        ];
    }
}
