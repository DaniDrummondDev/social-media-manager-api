<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AcceptInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }
}
