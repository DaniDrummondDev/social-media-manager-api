<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAdPerformanceInsightsRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:best_audiences,best_content_for_ads,organic_vs_paid_correlation'],
        ];
    }
}
