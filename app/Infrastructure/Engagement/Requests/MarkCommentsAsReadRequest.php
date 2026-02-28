<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MarkCommentsAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['required', 'uuid'],
        ];
    }
}
