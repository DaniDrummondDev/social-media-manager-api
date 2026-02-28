<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Controllers;

use App\Application\Engagement\DTOs\CreateAutomationRuleInput;
use App\Application\Engagement\DTOs\ListExecutionsInput;
use App\Application\Engagement\DTOs\UpdateAutomationRuleInput;
use App\Application\Engagement\UseCases\CreateAutomationRuleUseCase;
use App\Application\Engagement\UseCases\DeleteAutomationRuleUseCase;
use App\Application\Engagement\UseCases\ListAutomationRulesUseCase;
use App\Application\Engagement\UseCases\ListExecutionsUseCase;
use App\Application\Engagement\UseCases\UpdateAutomationRuleUseCase;
use App\Infrastructure\Engagement\Requests\CreateAutomationRuleRequest;
use App\Infrastructure\Engagement\Requests\UpdateAutomationRuleRequest;
use App\Infrastructure\Engagement\Resources\AutomationExecutionResource;
use App\Infrastructure\Engagement\Resources\AutomationRuleResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AutomationRuleController
{
    public function store(
        CreateAutomationRuleRequest $request,
        CreateAutomationRuleUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateAutomationRuleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            name: $request->validated('name'),
            priority: (int) $request->validated('priority'),
            conditions: $request->validated('conditions'),
            actionType: $request->validated('action_type'),
            responseTemplate: $request->validated('response_template'),
            webhookId: $request->validated('webhook_id'),
            delaySeconds: (int) ($request->validated('delay_seconds') ?? 120),
            dailyLimit: (int) ($request->validated('daily_limit') ?? 100),
            appliesToNetworks: $request->validated('applies_to_networks'),
            appliesToCampaigns: $request->validated('applies_to_campaigns'),
        ));

        return ApiResponse::success(
            AutomationRuleResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        Request $request,
        ListAutomationRulesUseCase $useCase,
    ): JsonResponse {
        $rules = $useCase->execute($request->attributes->get('auth_organization_id'));

        $data = array_map(
            fn ($item) => AutomationRuleResource::fromOutput($item)->toArray(),
            $rules,
        );

        return ApiResponse::success($data);
    }

    public function update(
        UpdateAutomationRuleRequest $request,
        UpdateAutomationRuleUseCase $useCase,
        string $id,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateAutomationRuleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            ruleId: $id,
            name: $request->validated('name'),
            priority: $request->has('priority') ? (int) $request->validated('priority') : null,
            conditions: $request->validated('conditions'),
            actionType: $request->validated('action_type'),
            responseTemplate: $request->validated('response_template'),
            webhookId: $request->validated('webhook_id'),
            delaySeconds: $request->has('delay_seconds') ? (int) $request->validated('delay_seconds') : null,
            dailyLimit: $request->has('daily_limit') ? (int) $request->validated('daily_limit') : null,
            appliesToNetworks: $request->validated('applies_to_networks'),
            appliesToCampaigns: $request->validated('applies_to_campaigns'),
        ));

        return ApiResponse::success(
            AutomationRuleResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        DeleteAutomationRuleUseCase $useCase,
        string $id,
    ): JsonResponse {
        $useCase->execute($request->attributes->get('auth_organization_id'), $id);

        return ApiResponse::success(null, status: 204);
    }

    public function executions(
        Request $request,
        ListExecutionsUseCase $useCase,
        string $id,
    ): JsonResponse {
        $executions = $useCase->execute(new ListExecutionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            ruleId: $id,
            cursor: $request->query('cursor'),
            limit: min((int) ($request->query('limit') ?? 20), 100),
        ));

        $data = array_map(
            fn ($item) => AutomationExecutionResource::fromOutput($item)->toArray(),
            $executions,
        );

        return ApiResponse::success($data);
    }
}
