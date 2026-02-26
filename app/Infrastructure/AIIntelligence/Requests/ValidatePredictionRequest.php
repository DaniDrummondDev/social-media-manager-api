<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ValidatePredictionRequest extends FormRequest
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
            'prediction_id' => ['required', 'string', 'uuid'],
            'content_id' => ['required', 'string', 'uuid'],
            'provider' => ['required', 'string', 'in:instagram,tiktok,youtube'],
            'actual_engagement_rate' => ['required', 'numeric', 'min:0'],
            'metrics_snapshot' => ['required', 'array'],
            'metrics_captured_at' => ['required', 'string', 'date'],
        ];
    }
}
