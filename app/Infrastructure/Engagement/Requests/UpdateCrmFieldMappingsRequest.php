<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCrmFieldMappingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.smm_field' => ['required', 'string', 'max:100'],
            'mappings.*.crm_field' => ['required', 'string', 'max:100'],
            'mappings.*.transform' => ['sometimes', 'nullable', 'string', 'in:uppercase,lowercase,trim'],
        ];
    }
}
