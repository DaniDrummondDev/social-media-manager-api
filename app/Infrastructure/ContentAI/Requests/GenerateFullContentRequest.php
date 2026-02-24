<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateFullContentRequest extends FormRequest
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
            'social_networks' => ['required', 'array', 'min:1', 'max:5'],
            'social_networks.*' => ['string', 'in:instagram,tiktok,youtube'],
            'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
            'keywords' => ['sometimes', 'array', 'max:10'],
            'keywords.*' => ['string', 'min:1', 'max:50'],
            'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
        ];
    }
}
