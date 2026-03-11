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
        $mode = $this->input('generation_mode', 'fields_only');

        return [
            'generation_mode' => ['sometimes', 'string', 'in:fields_only,brief_only,brief_and_fields'],
            'campaign_id' => [$mode !== 'fields_only' ? 'required' : 'sometimes', 'string', 'uuid'],
            'topic' => [$mode === 'brief_only' ? 'sometimes' : 'required', 'string', 'min:10', 'max:500'],
            'social_network' => ['sometimes', 'nullable', 'string', 'in:instagram,tiktok,youtube'],
            'tone' => ['sometimes', 'nullable', 'string', 'in:professional,casual,fun,informative,inspirational,custom'],
            'language' => ['sometimes', 'nullable', 'string', 'in:pt_BR,en_US,es_ES'],
        ];
    }
}
