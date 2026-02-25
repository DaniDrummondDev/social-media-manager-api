<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExportReportRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:overview,network,content'],
            'format' => ['required', 'string', 'in:pdf,csv'],
            'period' => ['sometimes', 'string', 'in:7d,30d,90d,custom'],
            'from' => ['required_if:period,custom', 'nullable', 'date'],
            'to' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:from'],
            'filter_provider' => ['sometimes', 'string', 'in:instagram,tiktok,youtube'],
            'filter_campaign_id' => ['sometimes', 'uuid'],
            'filter_content_id' => ['sometimes', 'uuid'],
        ];
    }
}
