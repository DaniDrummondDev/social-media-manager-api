<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListGapAnalysesRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
