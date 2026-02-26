<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PredictPerformanceRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'providers' => ['required', 'array', 'min:1', 'max:5'],
            'providers.*' => ['string', 'in:instagram,tiktok,youtube'],
            'detailed' => ['nullable', 'boolean'],
        ];
    }
}
