<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetContentRecommendationsRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'provider' => ['nullable', 'string', 'in:instagram,tiktok,youtube'],
        ];
    }
}
