<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Controllers;

use App\Application\AIIntelligence\DTOs\AcceptCalendarItemsInput;
use App\Application\AIIntelligence\DTOs\GetCalendarSuggestionInput;
use App\Application\AIIntelligence\DTOs\GenerateCalendarSuggestionsInput;
use App\Application\AIIntelligence\DTOs\ListCalendarSuggestionsInput;
use App\Application\AIIntelligence\UseCases\AcceptCalendarItemsUseCase;
use App\Application\AIIntelligence\UseCases\GenerateCalendarSuggestionsUseCase;
use App\Application\AIIntelligence\UseCases\GetCalendarSuggestionUseCase;
use App\Application\AIIntelligence\UseCases\ListCalendarSuggestionsUseCase;
use App\Infrastructure\AIIntelligence\Jobs\GenerateCalendarSuggestionsJob;
use App\Infrastructure\AIIntelligence\Requests\AcceptCalendarItemsRequest;
use App\Infrastructure\AIIntelligence\Requests\GenerateCalendarSuggestionsRequest;
use App\Infrastructure\AIIntelligence\Requests\ListCalendarSuggestionsRequest;
use App\Infrastructure\AIIntelligence\Resources\CalendarSuggestionListResource;
use App\Infrastructure\AIIntelligence\Resources\CalendarSuggestionResource;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CalendarSuggestionController
{
    public function suggest(
        GenerateCalendarSuggestionsRequest $request,
        GenerateCalendarSuggestionsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GenerateCalendarSuggestionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            periodStart: $request->validated('period_start'),
            periodEnd: $request->validated('period_end'),
        ));

        GenerateCalendarSuggestionsJob::dispatch(
            $output->suggestionId,
            $request->attributes->get('auth_organization_id'),
            $request->attributes->get('auth_user_id'),
            $request->validated('period_start'),
            $request->validated('period_end'),
        );

        return ApiResponse::success([
            'suggestion_id' => $output->suggestionId,
            'status' => $output->status,
            'message' => $output->message,
        ], status: 202);
    }

    public function index(
        ListCalendarSuggestionsRequest $request,
        ListCalendarSuggestionsUseCase $useCase,
    ): JsonResponse {
        $result = $useCase->execute(new ListCalendarSuggestionsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            cursor: $request->validated('cursor'),
            limit: (int) ($request->validated('limit') ?? 20),
        ));

        return ApiResponse::success(
            array_map(fn ($item) => CalendarSuggestionListResource::fromOutput($item)->toArray(), $result['items']),
            ['next_cursor' => $result['next_cursor']],
        );
    }

    public function show(
        Request $request,
        string $id,
        GetCalendarSuggestionUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new GetCalendarSuggestionInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            suggestionId: $id,
        ));

        return ApiResponse::success(
            CalendarSuggestionResource::fromOutput($output)->toArray(),
        );
    }

    public function accept(
        AcceptCalendarItemsRequest $request,
        string $id,
        AcceptCalendarItemsUseCase $useCase,
    ): JsonResponse {
        $output = $useCase->execute(new AcceptCalendarItemsInput(
            organizationId: $request->attributes->get('auth_organization_id'),
            userId: $request->attributes->get('auth_user_id'),
            suggestionId: $id,
            acceptedIndexes: $request->validated('accepted_indexes'),
        ));

        return ApiResponse::success([
            'id' => $output->id,
            'status' => $output->status,
            'accepted_count' => $output->acceptedCount,
            'total_count' => $output->totalCount,
        ]);
    }
}
