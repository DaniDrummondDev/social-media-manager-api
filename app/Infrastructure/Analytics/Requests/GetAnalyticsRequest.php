<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', 'string', 'in:7d,30d,90d,custom'],
            'from' => ['required_if:period,custom', 'nullable', 'date'],
            'to' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
