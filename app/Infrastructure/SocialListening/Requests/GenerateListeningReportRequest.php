<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateListeningReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query_ids' => ['required', 'array', 'min:1'],
            'query_ids.*' => ['string', 'uuid'],
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ];
    }
}
