<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublishNowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'social_account_ids' => ['required', 'array', 'min:1'],
            'social_account_ids.*' => ['required', 'uuid'],
        ];
    }
}
