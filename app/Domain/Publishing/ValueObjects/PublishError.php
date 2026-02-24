<?php

declare(strict_types=1);

namespace App\Domain\Publishing\ValueObjects;

final readonly class PublishError
{
    public function __construct(
        public string $code,
        public string $message,
        public bool $isPermanent,
    ) {}

    /**
     * @return array{code: string, message: string, is_permanent: bool}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'is_permanent' => $this->isPermanent,
        ];
    }
}
