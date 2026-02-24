<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class HandleOAuthCallbackRequest extends FormRequest
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
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ];
    }
}
