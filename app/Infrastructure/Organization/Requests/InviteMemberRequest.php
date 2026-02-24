<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,member'],
        ];
    }
}
