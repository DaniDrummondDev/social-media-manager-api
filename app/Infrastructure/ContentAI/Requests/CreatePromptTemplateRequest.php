<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePromptTemplateRequest extends FormRequest
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
            'generation_type' => ['required', 'string', 'in:title,description,hashtags,full_content,cross_network_adaptation,calendar_planning'],
            'version' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:200'],
            'system_prompt' => ['required', 'string', 'max:10000'],
            'user_prompt_template' => ['required', 'string', 'max:10000'],
            'variables' => ['sometimes', 'array'],
            'variables.*' => ['string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
