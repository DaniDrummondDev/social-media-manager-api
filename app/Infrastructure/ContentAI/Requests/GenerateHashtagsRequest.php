<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateHashtagsRequest extends FormRequest
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
            'topic' => ['required', 'string', 'min:10', 'max:500'],
            'niche' => ['sometimes', 'nullable', 'string', 'min:3', 'max:100'],
            'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
        ];
    }
}
