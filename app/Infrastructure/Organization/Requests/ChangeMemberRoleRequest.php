<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:owner,admin,member'],
        ];
    }
}
