<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateStyleProfileRequest extends FormRequest
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
        ];
    }
}
