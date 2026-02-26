<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

use App\Domain\Engagement\ValueObjects\CrmSyncResult;

final readonly class CrmSyncResultOutput
{
    public function __construct(
        public bool $success,
        public string $direction,
        public string $entityType,
        public string $action,
        public string $status,
        public ?string $externalId,
        public ?string $errorMessage,
    ) {}

    public static function fromValueObject(CrmSyncResult $result): self
    {
        return new self(
            success: $result->isSuccess(),
            direction: $result->direction->value,
            entityType: $result->entityType->value,
            action: $result->action,
            status: $result->status->value,
            externalId: $result->externalId,
            errorMessage: $result->errorMessage,
        );
    }
}
