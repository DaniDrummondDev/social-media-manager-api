<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSafetyRuleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rule_type' => ['required', 'string', 'in:blocked_word,required_disclosure,custom_check'],
            'rule_config' => ['required', 'array'],
            'severity' => ['required', 'string', 'in:warning,block'],
        ];
    }
}
