<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Contracts;

use App\Domain\ContentAI\Entities\PromptTemplate;
use App\Domain\Shared\ValueObjects\Uuid;

interface PromptTemplateRepositoryInterface
{
    public function create(PromptTemplate $template): void;

    public function update(PromptTemplate $template): void;

    public function findById(Uuid $id): ?PromptTemplate;

    /**
     * Find the best performing active template for auto-selection.
     * Requires minimum 20 uses and highest performance_score.
     */
    public function findBestPerformer(
        ?Uuid $organizationId,
        string $generationType,
    ): ?PromptTemplate;

    /**
     * Find the default template (is_default = true).
     * Falls back to system template (organization_id = NULL) if no org-level default.
     */
    public function findDefault(
        ?Uuid $organizationId,
        string $generationType,
    ): ?PromptTemplate;

    /**
     * @return array<PromptTemplate>
     */
    public function findActiveByOrganizationAndType(
        ?Uuid $organizationId,
        string $generationType,
    ): array;

    /**
     * @return array<PromptTemplate>
     */
    public function findAllActive(
        ?Uuid $organizationId = null,
        ?string $generationType = null,
    ): array;
}
