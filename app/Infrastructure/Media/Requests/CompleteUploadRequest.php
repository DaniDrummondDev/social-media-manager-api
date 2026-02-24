<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteUploadRequest extends FormRequest
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
            'checksum' => ['required', 'string', 'size:64'],
        ];
    }
}
