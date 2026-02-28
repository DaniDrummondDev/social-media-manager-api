<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AgentCallbackRequest extends FormRequest
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
            'correlation_id' => ['required', 'string', 'uuid'],
            'job_id' => ['required', 'string'],
            'status' => ['required', 'string', 'in:completed,failed'],
            'result' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'metadata.total_tokens' => ['nullable', 'integer', 'min:0'],
            'metadata.total_cost' => ['nullable', 'numeric', 'min:0'],
            'metadata.agents_used' => ['nullable', 'array'],
            'metadata.duration_ms' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
