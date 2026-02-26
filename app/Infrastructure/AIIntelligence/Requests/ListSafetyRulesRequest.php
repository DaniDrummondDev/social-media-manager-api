<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListSafetyRulesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
