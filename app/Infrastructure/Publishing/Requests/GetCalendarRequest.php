<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetCalendarRequest extends FormRequest
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
            'month' => ['sometimes', 'integer', 'between:1,12'],
            'year' => ['sometimes', 'integer', 'min:2020'],
            'start_date' => ['sometimes', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'provider' => ['sometimes', 'string', 'in:instagram,tiktok,youtube'],
            'campaign_id' => ['sometimes', 'uuid'],
        ];
    }
}
