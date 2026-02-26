<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecalculateBestTimesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', 'in:instagram,tiktok,youtube'],
            'social_account_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
