<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePlanRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly_cents' => ['required', 'integer', 'min:0'],
            'price_yearly_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'limits' => ['required', 'array'],
            'features' => ['required', 'array'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
