<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListCommentsRequest extends FormRequest
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
            'provider' => ['sometimes', 'string', 'in:instagram,tiktok,youtube'],
            'campaign_id' => ['sometimes', 'string', 'uuid'],
            'content_id' => ['sometimes', 'string', 'uuid'],
            'sentiment' => ['sometimes', 'string', 'in:positive,neutral,negative'],
            'is_read' => ['sometimes', 'boolean'],
            'is_replied' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:255'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'cursor' => ['sometimes', 'string', 'uuid'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
