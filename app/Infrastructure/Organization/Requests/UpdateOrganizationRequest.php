<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'slug' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,98}[a-z0-9])?$/'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ];
    }
}
