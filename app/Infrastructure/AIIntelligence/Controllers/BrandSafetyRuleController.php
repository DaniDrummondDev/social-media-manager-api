<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\CreateSafetyRuleInput;
use App\Application\AIIntelligence\DTOs\DeleteSafetyRuleInput;
use App\Application\AIIntelligence\DTOs\ListSafetyRulesInput;
use App\Application\AIIntelligence\DTOs\UpdateSafetyRuleInput;
use App\Application\AIIntelligence\UseCases\CreateSafetyRuleUseCase;
use App\Application\AIIntelligence\UseCases\DeleteSafetyRuleUseCase;
use App\Application\AIIntelligence\UseCases\ListSafetyRulesUseCase;
use App\Application\AIIntelligence\UseCases\UpdateSafetyRuleUseCase;
use App\Infrastructure\AIIntelligence\Requests\CreateSafetyRuleRequest;
use App\Infrastructure\AIIntelligence\Requests\ListSafetyRulesRequest;
use App\Infrastructure\AIIntelligence\Requests\UpdateSafetyRuleRequest;
use App\Infrastructure\AIIntelligence\Resources\SafetyRuleResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BrandSafetyRuleController
{
    public function store(
        CreateSafetyRuleRequest $request,
        CreateSafetyRuleUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new CreateSafetyRuleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            ruleType: $request->validated('rule_type'),
            ruleConfig: $request->validated('rule_config'),
            severity: $request->validated('severity'),
        ));

        return ApiResponse::success(
            SafetyRuleResource::fromOutput($output)->toArray(),
            status: 201,
        );
    }

    public function index(
        ListSafetyRulesRequest $request,
        ListSafetyRulesUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListSafetyRulesInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => SafetyRuleResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function update(
        UpdateSafetyRuleRequest $request,
        string $ruleId,
        UpdateSafetyRuleUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateSafetyRuleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            ruleId: $ruleId,
            ruleType: $request->validated('rule_type'),
            ruleConfig: $request->validated('rule_config'),
            severity: $request->validated('severity'),
        ));

        return ApiResponse::success(
            SafetyRuleResource::fromOutput($output)->toArray(),
        );
    }

    public function destroy(
        Request $request,
        string $ruleId,
        DeleteSafetyRuleUseCase $useCase,
    ): JsonResponse {
        $useCase->execute(new DeleteSafetyRuleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            ruleId: $ruleId,
        ));

        return ApiResponse::noContent();
    }
}
