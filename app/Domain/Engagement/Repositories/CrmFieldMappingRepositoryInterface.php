<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\ValueObjects\CrmFieldMapping;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

interface CrmFieldMappingRepositoryInterface
{
    /**
     * @return array<CrmFieldMapping>
     */
    public function findByConnectionId(Uuid $connectionId): array;

    /**
     * Replace all field mappings for a connection (bulk save).
     *
     * @param  array<CrmFieldMapping>  $mappings
     */
    public function saveForConnection(Uuid $connectionId, array $mappings): void;

    /**
     * Reset mappings to the default set for the given provider.
     */
    public function resetToDefault(Uuid $connectionId, CrmProvider $provider): void;

    /**
     * Get the default field mappings for a provider.
     *
     * @return array<CrmFieldMapping>
     */
    public function findDefaultByProvider(CrmProvider $provider): array;
}
