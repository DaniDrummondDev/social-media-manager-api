<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListMentionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query_id' => ['nullable', 'string', 'uuid'],
            'platform' => ['nullable', 'string', 'in:instagram,tiktok,youtube'],
            'sentiment' => ['nullable', 'string', 'in:positive,neutral,negative'],
            'is_flagged' => ['nullable', 'boolean'],
            'is_read' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'cursor' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
