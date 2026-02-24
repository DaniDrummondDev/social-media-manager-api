<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateContentRequest extends FormRequest
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
            'title' => ['sometimes', 'nullable', 'string', 'max:500'],
            'body' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'hashtags' => ['sometimes', 'nullable', 'array', 'max:30'],
            'hashtags.*' => ['string', 'min:1', 'max:100'],
            'media_ids' => ['sometimes', 'nullable', 'array'],
            'media_ids.*' => ['string', 'uuid'],
            'network_overrides' => ['sometimes', 'nullable', 'array'],
            'network_overrides.*.provider' => ['required', 'string', 'in:instagram,tiktok,youtube'],
            'network_overrides.*.title' => ['sometimes', 'nullable', 'string', 'max:500'],
            'network_overrides.*.body' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'network_overrides.*.hashtags' => ['sometimes', 'nullable', 'array', 'max:30'],
            'network_overrides.*.hashtags.*' => ['string', 'min:1', 'max:100'],
        ];
    }
}
