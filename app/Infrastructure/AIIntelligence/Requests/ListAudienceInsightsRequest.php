<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAudienceInsightsRequest extends FormRequest
{
    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:preferred_topics,sentiment_trends,engagement_drivers,audience_preferences'],
            'social_account_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
