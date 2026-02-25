<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Entities;

use App\Domain\Engagement\Exceptions\InvalidBlacklistRegexException;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class BlacklistWord
{
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public string $word,
        public bool $isRegex,
        public DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        Uuid $organizationId,
        string $word,
        bool $isRegex = false,
    ): self {
        if ($isRegex) {
            $test = @preg_match("/{$word}/i", '');
            if ($test === false) {
                throw new InvalidBlacklistRegexException(
                    "Expressão regular inválida: '{$word}'.",
                );
            }
        }

        return new self(
            id: Uuid::generate(),
            organizationId: $organizationId,
            word: $word,
            isRegex: $isRegex,
            createdAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        string $word,
        bool $isRegex,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            word: $word,
            isRegex: $isRegex,
            createdAt: $createdAt,
        );
    }

    public function matches(string $text): bool
    {
        if ($this->isRegex) {
            return (bool) preg_match("/{$this->word}/i", $text);
        }

        return str_contains(mb_strtolower($text), mb_strtolower($this->word));
    }
}
