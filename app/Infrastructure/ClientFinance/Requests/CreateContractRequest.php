<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateContractRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:fixed_monthly,per_campaign,per_post,hourly'],
            'value_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'in:BRL,USD,EUR'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'social_account_ids' => ['nullable', 'array'],
            'social_account_ids.*' => ['string', 'uuid'],
        ];
    }
}
