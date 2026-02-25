<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateClientRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.street' => ['nullable', 'string', 'max:255'],
            'billing_address.number' => ['nullable', 'string', 'max:20'],
            'billing_address.complement' => ['nullable', 'string', 'max:100'],
            'billing_address.neighborhood' => ['nullable', 'string', 'max:100'],
            'billing_address.city' => ['nullable', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:50'],
            'billing_address.zip_code' => ['nullable', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
