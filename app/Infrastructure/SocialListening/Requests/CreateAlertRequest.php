<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAlertRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'query_ids' => ['required', 'array', 'min:1'],
            'query_ids.*' => ['string', 'uuid'],
            'condition_type' => ['required', 'string', 'in:volume_spike,negative_sentiment_spike,keyword_detected,influencer_mention'],
            'threshold' => ['required', 'integer', 'min:1'],
            'window_minutes' => ['required', 'integer', 'min:1'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', 'in:email,webhook'],
            'cooldown_minutes' => ['required', 'integer', 'min:1'],
        ];
    }
}
