<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOrganizationsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:active,suspended,deleted'],
            'plan' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:200'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sort' => ['nullable', 'string', 'in:created_at,-created_at,name,-name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ];
    }
}
