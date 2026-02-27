<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchInterestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'string'],
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
