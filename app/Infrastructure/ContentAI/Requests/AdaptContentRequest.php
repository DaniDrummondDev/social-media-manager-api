<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdaptContentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'content_id' => ['required', 'string', 'uuid'],
            'source_network' => ['required', 'string', 'in:instagram,tiktok,youtube'],
            'target_networks' => ['required', 'array', 'min:1', 'max:5'],
            'target_networks.*' => ['string', 'in:instagram,tiktok,youtube'],
            'preserve_tone' => ['nullable', 'boolean'],
        ];
    }
}
