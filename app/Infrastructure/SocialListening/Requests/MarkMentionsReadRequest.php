<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MarkMentionsReadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mention_ids' => ['required', 'array', 'min:1'],
            'mention_ids.*' => ['string', 'uuid'],
        ];
    }
}
