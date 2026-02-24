<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class Verify2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'temp_token' => ['required', 'string'],
            'otp_code' => ['required', 'string', 'size:6'],
        ];
    }
}
