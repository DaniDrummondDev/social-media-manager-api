<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Contracts;

use App\Domain\ContentAI\Entities\PromptExperiment;
use App\Domain\Shared\ValueObjects\Uuid;

interface PromptExperimentRepositoryInterface
{
    public function create(PromptExperiment $experiment): void;

    public function update(PromptExperiment $experiment): void;

    public function findById(Uuid $id): ?PromptExperiment;

    /**
     * Find the running experiment for a given org + generation type.
     * Max 1 running experiment per (organization_id, generation_type).
     */
    public function findRunning(
        Uuid $organizationId,
        string $generationType,
    ): ?PromptExperiment;

    /**
     * Check if there's already a running experiment for this org + type.
     */
    public function hasRunningExperiment(
        Uuid $organizationId,
        string $generationType,
    ): bool;
}
