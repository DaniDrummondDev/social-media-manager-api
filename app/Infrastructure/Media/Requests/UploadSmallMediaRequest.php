<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadSmallMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240'],
            'checksum' => ['required', 'string', 'size:64'],
        ];
    }
}
