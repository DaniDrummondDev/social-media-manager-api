<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAudienceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'targeting_spec' => ['required', 'array'],
            'targeting_spec.demographics' => ['sometimes', 'array'],
            'targeting_spec.locations' => ['sometimes', 'array'],
            'targeting_spec.interests' => ['sometimes', 'array'],
        ];
    }
}
