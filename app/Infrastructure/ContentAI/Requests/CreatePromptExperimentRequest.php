<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePromptExperimentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'variant_a_id' => ['required', 'string', 'uuid'],
            'variant_b_id' => ['required', 'string', 'uuid'],
            'traffic_split' => ['sometimes', 'numeric', 'min:0.1', 'max:0.9'],
            'min_sample_size' => ['sometimes', 'integer', 'min:10', 'max:1000'],
        ];
    }
}
