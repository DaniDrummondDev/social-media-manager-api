<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteOrganizationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'confirm' => ['required', 'boolean', 'accepted'],
        ];
    }
}
