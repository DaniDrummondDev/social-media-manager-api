<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AllocateCostRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string', 'uuid'],
            'resource_type' => ['required', 'string', 'in:campaign,ai_generation,media_storage,publication'],
            'resource_id' => ['nullable', 'string', 'uuid'],
            'description' => ['required', 'string', 'min:2', 'max:500'],
            'cost_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'in:BRL,USD,EUR'],
        ];
    }
}
