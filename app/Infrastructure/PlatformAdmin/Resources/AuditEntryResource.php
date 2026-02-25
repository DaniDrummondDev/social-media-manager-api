<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AuditEntryOutput;

final readonly class AuditEntryResource
{
    private function __construct(
        private AuditEntryOutput $output,
    ) {}

    public static function fromOutput(AuditEntryOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'audit_entry',
            'attributes' => [
                'action' => $this->output->action,
                'admin' => $this->output->admin,
                'resource_type' => $this->output->resourceType,
                'resource_id' => $this->output->resourceId,
                'context' => $this->output->context,
                'ip_address' => $this->output->ipAddress,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
