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
            // SECURITY FIX (VALIDATION-001): Add max limit to prevent DoS attacks
            'media_ids' => ['sometimes', 'nullable', 'array', 'max:20'],
            'media_ids.*' => ['string', 'uuid'],
            // SECURITY FIX (VALIDATION-001): Add max limit to prevent DoS attacks
            'network_overrides' => ['sometimes', 'nullable', 'array', 'max:10'],
            'network_overrides.*.provider' => ['required', 'string', 'in:instagram,tiktok,youtube'],
            'network_overrides.*.title' => ['sometimes', 'nullable', 'string', 'max:500'],
            'network_overrides.*.body' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'network_overrides.*.hashtags' => ['sometimes', 'nullable', 'array', 'max:30'],
            'network_overrides.*.hashtags.*' => ['string', 'min:1', 'max:100'],
        ];
    }
}
