<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCampaignRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'min:3', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'starts_at' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d\TH:i:s\Z'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d\TH:i:s\Z', 'after:starts_at'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'min:1', 'max:50'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'brief_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'brief_target_audience' => ['sometimes', 'nullable', 'string', 'max:500'],
            'brief_restrictions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'brief_cta' => ['sometimes', 'nullable', 'string', 'max:500'],
            'clear_brief' => ['sometimes', 'boolean'],
        ];
    }
}
