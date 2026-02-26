<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateGapAnalysisRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'competitor_query_ids' => ['required', 'array', 'min:1'],
            'competitor_query_ids.*' => ['required', 'string', 'uuid'],
            'period_days' => ['nullable', 'integer', 'min:7', 'max:90'],
        ];
    }
}
