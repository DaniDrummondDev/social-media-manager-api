<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

final readonly class CrmSyncResult
{
    private function __construct(
        public CrmSyncDirection $direction,
        public CrmEntityType $entityType,
        public string $action,
        public CrmSyncStatus $status,
        public ?string $externalId,
        public ?string $errorMessage,
    ) {}

    public static function success(
        CrmSyncDirection $direction,
        CrmEntityType $entityType,
        string $action,
        string $externalId,
    ): self {
        return new self(
            direction: $direction,
            entityType: $entityType,
            action: $action,
            status: CrmSyncStatus::Success,
            externalId: $externalId,
            errorMessage: null,
        );
    }

    public static function failed(
        CrmSyncDirection $direction,
        CrmEntityType $entityType,
        string $action,
        string $errorMessage,
    ): self {
        return new self(
            direction: $direction,
            entityType: $entityType,
            action: $action,
            status: CrmSyncStatus::Failed,
            externalId: null,
            errorMessage: $errorMessage,
        );
    }

    public static function partial(
        CrmSyncDirection $direction,
        CrmEntityType $entityType,
        string $action,
        ?string $externalId,
        string $errorMessage,
    ): self {
        return new self(
            direction: $direction,
            entityType: $entityType,
            action: $action,
            status: CrmSyncStatus::Partial,
            externalId: $externalId,
            errorMessage: $errorMessage,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }
}
