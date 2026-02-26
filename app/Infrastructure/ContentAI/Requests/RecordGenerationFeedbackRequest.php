<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecordGenerationFeedbackRequest extends FormRequest
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
            'generation_id' => ['required', 'string', 'uuid'],
            'action' => ['required', 'string', 'in:accepted,edited,rejected'],
            'original_output' => ['required', 'array'],
            'edited_output' => ['required_if:action,edited', 'nullable', 'array'],
            'content_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'generation_type' => ['required', 'string', 'in:title,description,hashtags,full_content,cross_network_adaptation,calendar_planning'],
            'time_to_decision_ms' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
