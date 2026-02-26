<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateCalendarSuggestionsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date', 'after_or_equal:today'],
            'period_end' => ['required', 'date', 'after:period_start'],
        ];
    }
}
