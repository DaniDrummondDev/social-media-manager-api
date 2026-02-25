<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string', 'uuid'],
            'contract_id' => ['nullable', 'string', 'uuid'],
            'reference_month' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'min:2', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents' => ['required', 'integer', 'min:1'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'in:BRL,USD,EUR'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
