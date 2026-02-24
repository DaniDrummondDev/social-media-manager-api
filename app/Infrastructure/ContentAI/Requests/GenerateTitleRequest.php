<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateTitleRequest extends FormRequest
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
            'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
            'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
            'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
        ];
    }
}
