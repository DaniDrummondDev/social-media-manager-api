<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAlertRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'query_ids' => ['nullable', 'array', 'min:1'],
            'query_ids.*' => ['string', 'uuid'],
            'condition_type' => ['nullable', 'string', 'in:volume_spike,negative_sentiment_spike,keyword_detected,influencer_mention'],
            'threshold' => ['nullable', 'integer', 'min:1'],
            'window_minutes' => ['nullable', 'integer', 'min:1'],
            'channels' => ['nullable', 'array', 'min:1'],
            'channels.*' => ['string', 'in:email,webhook'],
            'cooldown_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
