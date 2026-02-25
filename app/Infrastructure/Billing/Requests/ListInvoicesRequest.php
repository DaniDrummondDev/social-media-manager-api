<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListInvoicesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:paid,open,void,uncollectible'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'cursor' => ['sometimes', 'string', 'uuid'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
