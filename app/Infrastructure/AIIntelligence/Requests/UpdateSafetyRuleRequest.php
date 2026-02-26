<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSafetyRuleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rule_type' => ['nullable', 'string', 'in:blocked_word,required_disclosure,custom_check'],
            'rule_config' => ['nullable', 'array'],
            'severity' => ['nullable', 'string', 'in:warning,block'],
        ];
    }
}
