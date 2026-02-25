<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MarkInvoicePaidRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:pix,boleto,bank_transfer,credit_card,other'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
