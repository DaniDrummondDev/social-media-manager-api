<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListCostAllocationsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'uuid'],
            'resource_type' => ['nullable', 'string', 'in:campaign,ai_generation,media_storage,publication'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'cursor' => ['nullable', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
