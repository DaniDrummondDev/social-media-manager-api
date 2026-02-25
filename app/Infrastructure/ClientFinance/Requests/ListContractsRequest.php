<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListContractsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:active,paused,completed,cancelled'],
            'cursor' => ['nullable', 'string', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
