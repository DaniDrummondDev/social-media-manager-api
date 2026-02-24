<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InitiateOAuthRequest extends FormRequest
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
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string'],
        ];
    }
}
