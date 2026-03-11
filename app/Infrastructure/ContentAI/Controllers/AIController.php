<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Controllers;

use App\Application\ContentAI\DTOs\GenerateDescriptionInput;
use App\Application\ContentAI\DTOs\GenerateFullContentInput;
use App\Application\ContentAI\DTOs\GenerateHashtagsInput;
use App\Application\ContentAI\DTOs\GenerateTitleInput;
use App\Application\ContentAI\DTOs\UpdateAISettingsInput;
use App\Application\ContentAI\UseCases\GenerateDescriptionUseCase;
use App\Application\ContentAI\UseCases\GenerateFullContentUseCase;
use App\Application\ContentAI\UseCases\GenerateHashtagsUseCase;
use App\Application\ContentAI\UseCases\GenerateTitleUseCase;
use App\Application\ContentAI\UseCases\GetAISettingsUseCase;
use App\Application\ContentAI\UseCases\ListAIHistoryUseCase;
use App\Application\ContentAI\UseCases\UpdateAISettingsUseCase;
use App\Infrastructure\ContentAI\Requests\GenerateDescriptionRequest;
use App\Infrastructure\ContentAI\Requests\GenerateFullContentRequest;
use App\Infrastructure\ContentAI\Requests\GenerateHashtagsRequest;
use App\Infrastructure\ContentAI\Requests\GenerateTitleRequest;
use App\Infrastructure\ContentAI\Requests\UpdateAISettingsRequest;
use App\Infrastructure\ContentAI\Resources\AIGenerationResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AIController
{
    public function generateTitle(
        GenerateTitleRequest $request,
        GenerateTitleUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateTitleInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            topic: $request->validated('topic', ''),
            socialNetwork: $request->validated('social_network'),
            tone: $request->validated('tone'),
            language: $request->validated('language'),
            campaignId: $request->validated('campaign_id'),
            generationMode: $request->validated('generation_mode', 'fields_only'),
        ));

        return ApiResponse::success(AIGenerationResource::fromOutput($output)->toArray());
    }

    public function generateDescription(
        GenerateDescriptionRequest $request,
        GenerateDescriptionUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateDescriptionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            topic: $request->validated('topic', ''),
            socialNetwork: $request->validated('social_network'),
            tone: $request->validated('tone'),
            keywords: $request->validated('keywords', []),
            language: $request->validated('language'),
            campaignId: $request->validated('campaign_id'),
            generationMode: $request->validated('generation_mode', 'fields_only'),
        ));

        return ApiResponse::success(AIGenerationResource::fromOutput($output)->toArray());
    }

    public function generateHashtags(
        GenerateHashtagsRequest $request,
        GenerateHashtagsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateHashtagsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            topic: $request->validated('topic', ''),
            niche: $request->validated('niche'),
            socialNetwork: $request->validated('social_network'),
            campaignId: $request->validated('campaign_id'),
            generationMode: $request->validated('generation_mode', 'fields_only'),
        ));

        return ApiResponse::success(AIGenerationResource::fromOutput($output)->toArray());
    }

    public function generateContent(
        GenerateFullContentRequest $request,
        GenerateFullContentUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateFullContentInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            topic: $request->validated('topic', ''),
            socialNetworks: $request->validated('social_networks'),
            tone: $request->validated('tone'),
            keywords: $request->validated('keywords', []),
            language: $request->validated('language'),
            campaignId: $request->validated('campaign_id'),
            generationMode: $request->validated('generation_mode', 'fields_only'),
        ));

        return ApiResponse::success(AIGenerationResource::fromOutput($output)->toArray());
    }

    public function getSettings(
        Request $request,
        GetAISettingsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute($request->attributes->get('auth_organization_id'));

        return ApiResponse::success([
            'default_tone' => $output->defaultTone,
            'custom_tone_description' => $output->customToneDescription,
            'default_language' => $output->defaultLanguage,
            'monthly_generation_limit' => $output->monthlyGenerationLimit,
            'usage_this_month' => $output->usageThisMonth,
        ]);
    }

    public function updateSettings(
        UpdateAISettingsRequest $request,
        UpdateAISettingsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new UpdateAISettingsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            defaultTone: $request->validated('default_tone'),
            customToneDescription: $request->validated('custom_tone_description'),
            defaultLanguage: $request->validated('default_language'),
        ));

        return ApiResponse::success([
            'default_tone' => $output->defaultTone,
            'custom_tone_description' => $output->customToneDescription,
            'default_language' => $output->defaultLanguage,
            'monthly_generation_limit' => $output->monthlyGenerationLimit,
            'usage_this_month' => $output->usageThisMonth,
        ]);
    }

    public function history(
        Request $request,
        ListAIHistoryUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(
            $request->attributes->get('auth_organization_id'),
            $request->query('type'),
        );

        $data = array_map(
            fn ($item) => AIGenerationResource::fromOutput($item)->toArray(),
            $output->items,
        );

        return ApiResponse::success($data);
    }
}
