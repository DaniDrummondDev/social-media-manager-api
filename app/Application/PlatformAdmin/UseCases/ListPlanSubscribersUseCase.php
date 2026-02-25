<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\UseCases;

use App\Application\PlatformAdmin\Contracts\PlatformQueryServiceInterface;
use App\Application\PlatformAdmin\DTOs\AdminOrganizationOutput;
use App\Application\PlatformAdmin\DTOs\ListPlanSubscribersInput;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListPlanSubscribersUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly PlatformQueryServiceInterface $queryService,
    ) {}

    /**
     * @return array{items: array<AdminOrganizationOutput>, next_cursor: ?string, has_more: bool}
     */
    public function execute(ListPlanSubscribersInput $input): array
    {
        $plan = $this->planRepository->findById(Uuid::fromString($input->planId));

        if ($plan === null) {
            throw new DomainException(
                'Plano não encontrado.',
                'PLAN_NOT_FOUND',
            );
        }

        $filters = array_filter([
            'subscription_status' => $input->subscriptionStatus,
            'sort' => $input->sort,
        ], fn ($v) => $v !== null);

        $result = $this->queryService->listPlanSubscribers(
            $input->planId,
            $filters,
            $input->perPage,
            $input->cursor,
        );

        $items = array_map(
            fn (array $data) => new AdminOrganizationOutput(
                id: $data['id'],
                name: $data['name'],
                status: $data['status'],
                plan: $data['plan'] ?? null,
                membersCount: (int) ($data['members_count'] ?? 0),
                socialAccountsCount: (int) ($data['social_accounts_count'] ?? 0),
                owner: $data['owner'] ?? null,
                subscriptionStatus: $data['subscription_status'] ?? null,
                createdAt: $data['created_at'],
            ),
            $result['items'],
        );

        return [
            'items' => $items,
            'next_cursor' => $result['next_cursor'],
            'has_more' => $result['has_more'],
        ];
    }
}
