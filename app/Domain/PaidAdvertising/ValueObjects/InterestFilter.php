<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

final readonly class InterestFilter
{
    /**
     * @param  array<array{id: string, name: string}>  $interests
     * @param  array<array{id: string, name: string}>  $behaviors
     * @param  array<string>  $keywords
     */
    private function __construct(
        public array $interests,
        public array $behaviors,
        public array $keywords,
    ) {}

    /**
     * @param  array<array{id: string, name: string}>  $interests
     * @param  array<array{id: string, name: string}>  $behaviors
     * @param  array<string>  $keywords
     */
    public static function create(
        array $interests = [],
        array $behaviors = [],
        array $keywords = [],
    ): self {
        return new self($interests, $behaviors, $keywords);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            interests: (array) ($data['interests'] ?? []),
            behaviors: (array) ($data['behaviors'] ?? []),
            keywords: (array) ($data['keywords'] ?? []),
        );
    }

    public function isEmpty(): bool
    {
        return $this->interests === []
            && $this->behaviors === []
            && $this->keywords === [];
    }

    public function equals(self $other): bool
    {
        return $this->interests === $other->interests
            && $this->behaviors === $other->behaviors
            && $this->keywords === $other->keywords;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'interests' => $this->interests,
            'behaviors' => $this->behaviors,
            'keywords' => $this->keywords,
        ];
    }
}
