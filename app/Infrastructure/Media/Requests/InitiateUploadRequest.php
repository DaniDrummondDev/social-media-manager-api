<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InitiateUploadRequest extends FormRequest
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
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:100'],
            'total_bytes' => ['required', 'integer', 'min:1'],
            'chunk_size_bytes' => ['sometimes', 'integer', 'min:1048576', 'max:10485760'],
        ];
    }
}
