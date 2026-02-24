<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAISettingsRequest extends FormRequest
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
            'default_tone' => ['sometimes', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
            'custom_tone_description' => ['required_if:default_tone,custom', 'nullable', 'string', 'max:500'],
            'default_language' => ['sometimes', 'string', 'in:pt_BR,en_US,es_ES'],
        ];
    }
}
