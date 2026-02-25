<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RescheduleRequest extends FormRequest
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
            'scheduled_at' => ['required', 'date', 'date_format:Y-m-d\TH:i:s\Z', 'after:+4 minutes'],
        ];
    }
}
