<?php

declare(strict_types=1);

namespace App\Domain\Identity\ValueObjects;

final readonly class HashedPassword
{
    private function __construct(
        public string $hash,
    ) {}

    private const ALGORITHM = PASSWORD_ARGON2ID;

    private const OPTIONS = [
        'memory_cost' => 65536,  // 64MB
        'time_cost' => 4,        // 4 iterações
        'threads' => 2,          // 2 threads paralelas
    ];

    public static function fromPlainText(string $plainText): self
    {
        return new self(password_hash($plainText, self::ALGORITHM, self::OPTIONS));
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plainText): bool
    {
        return password_verify($plainText, $this->hash);
    }

    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hash, self::ALGORITHM, self::OPTIONS);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
