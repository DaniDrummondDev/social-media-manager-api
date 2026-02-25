<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\Entities\BlacklistWord;

final readonly class BlacklistWordOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $word,
        public bool $isRegex,
        public string $createdAt,
    ) {}

    public static function fromEntity(BlacklistWord $word): self
    {
        return new self(
            id: (string) $word->id,
            organizationId: (string) $word->organizationId,
            word: $word->word,
            isRegex: $word->isRegex,
            createdAt: $word->createdAt->format('c'),
        );
    }
}
