<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\Contracts;

interface PlatformQueryServiceInterface
{
    // ─── Read: Dashboard Metrics ─────────────────────────────────

    /** @return array{total: int, active: int, suspended: int} */
    public function countOrganizations(): array;

    /** @return array{total: int, active_30d: int} */
    public function countUsers(): array;

    /** @return array<string, int> slug => count */
    public function countSubscriptionsByPlan(): array;

    /** @return array{trialing: int, past_due: int} */
    public function countSubscriptionsByStatus(): array;

    public function calculateMrrCents(): int;

    public function calculateArrCents(): int;

    /** @return array{publications: int, ai_generations: int} */
    public function getUsageToday(): array;

    public function getStorageUsedGb(): float;

    public function countActiveSocialAccounts(): int;

    /** @return array{success_rate: float, avg_latency_ms: int, providers: array<string, string>} */
    public function getPublishingHealth24h(): array;

    // ─── Read: Organization Queries ──────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listOrganizations(array $filters, int $perPage, ?string $cursor): array;

    /** @return array<string, mixed>|null */
    public function getOrganizationDetail(string $organizationId): ?array;

    // ─── Read: User Queries ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listUsers(array $filters, int $perPage, ?string $cursor): array;

    /** @return array<string, mixed>|null */
    public function getUserDetail(string $userId): ?array;

    // ─── Read: Plan Queries ──────────────────────────────────────

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<array<string, mixed>>, next_cursor: ?string, has_more: bool}
     */
    public function listPlanSubscribers(string $planId, array $filters, int $perPage, ?string $cursor): array;

    public function countPlanSubscribers(string $planId): int;

    /** @return array<array<string, mixed>> All plans (active + inactive) with subscriber counts */
    public function listAllPlans(): array;

    // ─── Read: Admin Info ────────────────────────────────────────

    /** @return array{id: string, name: string, email: string}|null */
    public function getAdminInfo(string $adminId): ?array;

    // ─── Mutation: Organization ──────────────────────────────────

    public function suspendOrganization(string $id, string $reason): void;

    public function unsuspendOrganization(string $id): void;

    public function deleteOrganization(string $id): void;

    // ─── Mutation: User ──────────────────────────────────────────

    public function banUser(string $id, string $reason): void;

    public function unbanUser(string $id): void;

    public function forceVerifyUser(string $id): void;

    // ─── Mutation: Plan ──────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $data
     * @return string New plan UUID
     */
    public function createPlan(array $data): string;

    /** @param  array<string, mixed>  $data */
    public function updatePlan(string $planId, array $data): void;

    public function deactivatePlan(string $planId): void;
}
