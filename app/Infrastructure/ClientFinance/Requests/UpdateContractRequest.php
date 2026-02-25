<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateContractRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'value_cents' => ['nullable', 'integer', 'min:1'],
            'ends_at' => ['nullable', 'date'],
            'social_account_ids' => ['nullable', 'array'],
            'social_account_ids.*' => ['string', 'uuid'],
        ];
    }
}
