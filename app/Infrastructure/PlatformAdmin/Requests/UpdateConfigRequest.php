<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateConfigRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'configs' => ['required', 'array', 'min:1', 'max:10'],
            'configs.*.key' => ['required', 'string', 'max:100'],
            'configs.*.value' => ['required'],
        ];
    }
}
