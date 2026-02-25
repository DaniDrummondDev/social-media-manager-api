<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyCommentRequest extends FormRequest
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
            'text' => ['required', 'string', 'max:2000'],
        ];
    }
}
