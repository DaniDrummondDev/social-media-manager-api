<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateListeningQueryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:500'],
            'platforms' => ['nullable', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:instagram,tiktok,youtube'],
        ];
    }
}
