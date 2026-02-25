<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAutomationRuleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'conditions' => ['sometimes', 'array', 'min:1'],
            'conditions.*.field' => ['required_with:conditions', 'string', 'in:keyword,sentiment,author_name'],
            'conditions.*.operator' => ['required_with:conditions', 'string', 'in:contains,equals,in,not_contains'],
            'conditions.*.value' => ['required_with:conditions', 'string'],
            'conditions.*.is_case_sensitive' => ['sometimes', 'boolean'],
            'conditions.*.position' => ['sometimes', 'integer', 'min:0'],
            'action_type' => ['sometimes', 'string', 'in:reply_fixed,reply_template,reply_ai,send_webhook'],
            'response_template' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'webhook_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'delay_seconds' => ['sometimes', 'integer', 'min:0', 'max:86400'],
            'daily_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'applies_to_networks' => ['sometimes', 'nullable', 'array'],
            'applies_to_networks.*' => ['string', 'in:instagram,tiktok,youtube'],
            'applies_to_campaigns' => ['sometimes', 'nullable', 'array'],
            'applies_to_campaigns.*' => ['string', 'uuid'],
        ];
    }
}
