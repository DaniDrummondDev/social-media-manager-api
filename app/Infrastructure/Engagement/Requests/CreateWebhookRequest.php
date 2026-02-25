<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWebhookRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:https', 'max:2000'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:comment.created,comment.replied,automation.executed'],
            'headers' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
