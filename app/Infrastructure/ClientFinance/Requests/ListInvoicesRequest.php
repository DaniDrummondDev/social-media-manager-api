<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListInvoicesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'string', 'uuid'],
            'status' => ['nullable', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'reference_month' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'cursor' => ['nullable', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
