<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePortalRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'return_url' => ['required', 'url', 'max:2000'],
        ];
    }
}
