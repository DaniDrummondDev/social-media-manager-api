<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

final readonly class WebhookSecret
{
    private function __construct(
        public string $value,
    ) {}

    public static function generate(): self
    {
        return new self('whsec_'.bin2hex(random_bytes(32)));
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function sign(string $payload, int $timestamp): string
    {
        $signedContent = "{$timestamp}.{$payload}";

        return hash_hmac('sha256', $signedContent, $this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
