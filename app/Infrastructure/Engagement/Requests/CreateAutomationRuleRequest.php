<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAutomationRuleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.field' => ['required', 'string', 'in:keyword,sentiment,author_name'],
            'conditions.*.operator' => ['required', 'string', 'in:contains,equals,in,not_contains'],
            'conditions.*.value' => ['required', 'string'],
            'conditions.*.is_case_sensitive' => ['sometimes', 'boolean'],
            'conditions.*.position' => ['sometimes', 'integer', 'min:0'],
            'action_type' => ['required', 'string', 'in:reply_fixed,reply_template,reply_ai,send_webhook'],
            'response_template' => ['required_if:action_type,reply_fixed', 'required_if:action_type,reply_template', 'nullable', 'string', 'max:2000'],
            'webhook_id' => ['required_if:action_type,send_webhook', 'nullable', 'string', 'uuid'],
            'delay_seconds' => ['sometimes', 'integer', 'min:0', 'max:86400'],
            'daily_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'applies_to_networks' => ['sometimes', 'nullable', 'array'],
            'applies_to_networks.*' => ['string', 'in:instagram,tiktok,youtube'],
            'applies_to_campaigns' => ['sometimes', 'nullable', 'array'],
            'applies_to_campaigns.*' => ['string', 'uuid'],
        ];
    }
}
